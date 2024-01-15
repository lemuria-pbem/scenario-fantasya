<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Combat\Battle;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Market;
use Lemuria\Model\Fantasya\Building\Port;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Extension\Duty;
use Lemuria\Model\Fantasya\Extension\Fee;
use Lemuria\Model\Fantasya\Extension\Market as MarketExtension;
use Lemuria\Model\Fantasya\Extension\Trades;
use Lemuria\Model\Fantasya\ExtensionTrait;
use Lemuria\Model\Fantasya\Kind;
use Lemuria\Model\Fantasya\Market\Sales;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Engine\Event\CollectRumour;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Model\GoodKinds;
use Lemuria\Scenario\Fantasya\Model\Myth;
use Lemuria\Scenario\Fantasya\Model\Rumour;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\TranslateTrait;
use Lemuria\Storage\Ini\Section;

/**
 * Collect rumours to tell later.
 */
class Hearsay extends AbstractAct
{
	use ExtensionTrait;
	use TranslateTrait;

	protected const array ROUNDS = [
		Myth::Battle->name    => 3, Myth::Monster->name => 3,
		Myth::Encounter->name => 6,
		Myth::Fee->name       => 9, Myth::Market->name  => 9,
	];

	/**
	 * @var array<Rumour>
	 */
	private array $interest = [];

	/**
	 * @var array<int, array<string>>
	 */
	private array $rumours = [];

	private string $date;

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$n = $macro->count();
		if ($n > 0) {
			for ($i = 1; $i <= $n; $i++) {
				$topic = $macro->getParameter($i);
				$myth  = Myth::tryFrom($topic);
				if ($myth) {
					$this->interest[] = new Rumour($myth);
				} else {
					Lemuria::Log()->critical('Invalid hearsay myth given: ' . $topic);
				}
			}
		} else {
			foreach (Myth::cases() as $myth) {
				$this->interest[] = new Rumour($myth);
			}
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		CollectRumour::register($this);

		$myths = [];
		foreach ($this->interest as $rumour) {
			$myths[] = $rumour->Myth()->value;
		}
		$this->macro->setParameters($myths);

		return $this;
	}

	public function getChainResult(): bool {
		return true;
	}

	/**
	 * @return array<Rumour>
	 */
	public function Interest(): array {
		return $this->interest;
	}

	public function update(): void {
		foreach ($this->interest as $rumour) {
			$rumour->Area()->add($this->unit->Region());
		}
	}

	public function collect(): void {
		$this->initDictionary();
		$calendar   = Lemuria::Calendar();
		$this->date = 'in der ' . $calendar->Week() . '. Woche ' . $this->translateKey('calendar.month', $calendar->Month());
		foreach ($this->interest as $rumour) {
			match ($rumour->Myth()) {
				Myth::Battle    => $this->addBattleRumours($rumour->Incidents()),
				Myth::Encounter => $this->addEncounterRumours($rumour->Incidents()),
				Myth::Fee       => $this->addFeeRumours($rumour->Incidents()),
				Myth::Market    => $this->addMarketRumours($rumour->Incidents()),
				Myth::Monster   => $this->addMonsterRumours($rumour->Incidents())
			};
		}
		$this->createRumourSections();
	}

	protected function createRumourSections(): void {
		arsort($this->rumours);
		foreach ($this->rumours as $rounds => $rumours) {
			$section = new Section('Gerücht ' . $this->unit->Id());
			$section->Values()->offsetSet('Runden', (string)$rounds);
			foreach ($rumours as $rumour) {
				$section->Lines()->add($rumour);
			}
			$this->scene->Script()->add($section);
		}
	}

	/**
	 * @param \ArrayObject<Battle> $monsters
	 */
	private function addBattleRumours(\ArrayObject $battles): void {
		//TODO
	}

	/**
	 * @param \ArrayObject<Unit> $units
	 */
	private function addEncounterRumours(\ArrayObject $units): void {
		$r = self::ROUNDS[Myth::Encounter->name];
		foreach ($units as $unit) {
			$rumour              = $this->dictionary->random('hearsay.encounter');
			$rumour              = $this->translateReplace($rumour, '$date', $this->date);
			$rumour              = $this->translateReplace($rumour, '$name', $unit->Name());
			$rumour              = $this->translateReplace($rumour, '$pronoun', $this->pronoun($unit));
			$rumour              = $this->translateReplace($rumour, '$region', $unit->Region()->Name());
			$this->rumours[$r][] = $rumour;
		}
	}

	/**
	 * @param \ArrayObject<Construction> $constructions
	 */
	private function addFeeRumours(\ArrayObject $constructions): void {
		foreach ($constructions as $construction) {
			$extensions = $construction->Extensions();
			switch ($construction->Building()::class) {
				case Market::class :
					/** @var MarketExtension $market */
					$market = $extensions->offsetGet(MarketExtension::class);
					$this->addFeeRumour($construction, $market->Fee());
					break;
				case Port::class :
					/** @var Duty $duty */
					$duty = $extensions->offsetGet(Duty::class);
					$this->addDutyRumour($construction, $duty->Duty());
				default :
					/** @var Fee $fee */
					$fee = $extensions->offsetGet(Fee::class);
					$this->addFeeRumour($construction, $fee->Fee());
			}
		}
	}

	/**
	 * @param \ArrayObject<Construction> $markets
	 */
	private function addMarketRumours(\ArrayObject $markets): void {
		foreach ($markets as $market) {
			$offer  = [];
			$demand = [];
			$kinds  = $this->getMarketGoods($market);
			foreach ($kinds->Offer() as $kind) {
				$offer[] = $this->dictionary->get('kind' , $kind->name);
			}
			foreach ($kinds->Demand() as $kind) {
				$demand[] = $this->dictionary->get('kind' , $kind->name);
			}
			$this->addMarketRumour($market, $offer, $demand);
		}
	}

	/**
	 * @param \ArrayObject<Unit> $monsters
	 */
	private function addMonsterRumours(\ArrayObject $monsters): void {
		$r = self::ROUNDS[Myth::Monster->name];
		foreach ($monsters as $unit) {
			$rumour              = $this->dictionary->random('hearsay.monster', $unit->Size() > 1 ? 1 : 0);
			$rumour              = $this->translateReplace($rumour, '$date', $this->date);
			$rumour              = $this->translateReplace($rumour, '$monster', $unit->Race());
			$rumour              = $this->translateReplace($rumour, '$region', $unit->Region()->Name());
			$this->rumours[$r][] = $rumour;
		}
	}

	private function addFeeRumour(Construction $construction, Quantity|float|null $fee): void {
		$building = $construction->Building();
		$r        = self::ROUNDS[Myth::Fee->name];
		if ($fee instanceof Quantity) {
			$rumour = $this->dictionary->get('hearsay.fee.fixed', $building);
			$rumour = $this->translateReplace($rumour, '$fee', $this->translateItem($fee));
		} elseif (is_float($fee)) {
			$rumour = $this->dictionary->get('hearsay.fee.rate', $building);
			$rumour = $this->translateReplace($rumour, '$fee', (string)(int)round(100 * $fee));
		} else {
			$rumour = $this->dictionary->get('hearsay.fee.free', $building);
		}
		$rumour              = $this->translateReplace($rumour, '$name', $construction->Name());
		$rumour              = $this->translateReplace($rumour, '$region', $construction->Region()->Name());
		$this->rumours[$r][] = $rumour;
	}

	private function addDutyRumour(Construction $construction, float $duty): void {
		$building = $construction->Building();
		$r        = self::ROUNDS[Myth::Fee->name];
		if ($duty > 0.0) {
			$rumour = $this->dictionary->get('hearsay.duty.rate', $building);
			$rumour = $this->translateReplace($rumour, '$duty', (string)(int)round(100 * $duty));
		} else {
			$rumour = $this->dictionary->get('hearsay.duty.free', $building);
		}
		$rumour              = $this->translateReplace($rumour, '$name', $construction->Name());
		$rumour              = $this->translateReplace($rumour, '$region', $construction->Region()->Name());
		$this->rumours[$r][] = $rumour;
	}

	/**
	 * @return array<Kind>
	 */
	private function getMarketGoods(Construction $construction): GoodKinds {
		$kinds = new GoodKinds();
		$sales = new Sales($construction);
		foreach ($construction->Inhabitants() as $unit) {
			if ($unit !== $this->unit) {
				if ($unit->Extensions()->offsetExists(Trades::class)) {
					/** @var Trades $trades */
					$trades = $unit->Extensions()->offsetGet(Trades::class);
					foreach ($trades as $trade) {
						if ($sales->getStatus($trade) === Sales::AVAILABLE) {
							$kinds->addFrom($trade);
						}
					}
				}
			}
		}
		return $kinds;
	}

	/**
	 * @param array<string> $offer
	 * @param array<string> $demand
	 */
	private function addMarketRumour(Construction $market, array $offer, array $demand): void {
		$r = self::ROUNDS[Myth::Market->name];
		if (empty($offer)) {
			if (empty($demand)) {
				$rumour = $this->dictionary->get('hearsay.market.nothing');
			} else {
				$rumour = $this->dictionary->get('hearsay.market.demand');
				$rumour = $this->translateReplace($rumour, '$demand', $this->combineKinds($demand));
			}
		} else {
			if (empty($demand)) {
				$rumour = $this->dictionary->get('hearsay.market.offer');
				$rumour = $this->translateReplace($rumour, '$offer', $this->combineKinds($offer));
			} else {
				$rumour = $this->dictionary->get('hearsay.market.both');
				$rumour = $this->translateReplace($rumour, '$offer', $this->combineKinds($offer));
				$rumour = $this->translateReplace($rumour, '$demand', $this->combineKinds($demand));
			}
		}
		$rumour = $this->translateReplace($rumour, '$name', $market->Name());
		$rumour = $this->translateReplace($rumour, '$region', $market->Region()->Name());
		$this->rumours[$r][] = $rumour;
	}

	/**
	 * @param array<string> $kinds
	 */
	private function combineKinds(array $kinds): string {
		$last = array_pop($kinds);
		if (empty($kinds)) {
			return $last;
		}
		return implode(', ', $kinds) . ' und ' . $last;
	}
}
