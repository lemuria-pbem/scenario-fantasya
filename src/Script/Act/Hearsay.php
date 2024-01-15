<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Engine\Event\CollectRumour;
use Lemuria\Scenario\Fantasya\Macro;
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
	use TranslateTrait;

	protected const ROUNDS_MONSTER = 3;

	/**
	 * @var array<Rumour>
	 */
	private array $interest = [];

	/**
	 * @var array<int, array<string>>
	 */
	private array $rumours = [];

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
		//TODO
		$this->initDictionary();
		foreach ($this->interest as $rumour) {
			switch ($rumour->Myth()) {
				case Myth::Monster :
					$this->addMonsterRumours($rumour->Incidents());
					break;
			}
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

	private function addMonsterRumours(\ArrayObject $monsters): void {
		$date = 'Runde ' . Lemuria::Calendar()->Round();
		foreach ($monsters as $unit) {
			/** @var Unit $unit */
			$rumour                                = $this->dictionary->random('hearsay.monster', $unit->Size() > 1 ? 1 : 0);
			$rumour                                = $this->translateReplace($rumour, '$date', $date);
			$rumour                                = $this->translateReplace($rumour, '$monster', $unit->Race());
			$rumour                                = $this->translateReplace($rumour, '$region', $unit->Region()->Name());
			$this->rumours[self::ROUNDS_MONSTER][] = $rumour;
		}
	}
}
