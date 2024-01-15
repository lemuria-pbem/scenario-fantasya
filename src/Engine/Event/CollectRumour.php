<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Id;
use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Combat\Battle;
use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Market;
use Lemuria\Model\Fantasya\Building\Port;
use Lemuria\Model\Fantasya\Building\Quay;
use Lemuria\Model\Fantasya\Commodity\Monster\Ent;
use Lemuria\Model\Fantasya\Commodity\Monster\GiantFrog;
use Lemuria\Model\Fantasya\Commodity\Monster\Goblin;
use Lemuria\Model\Fantasya\Commodity\Monster\Sandworm;
use Lemuria\Model\Fantasya\Commodity\Monster\Skeleton;
use Lemuria\Model\Fantasya\Commodity\Monster\Wolf;
use Lemuria\Model\Fantasya\Commodity\Monster\Zombie;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Model\Myth;
use Lemuria\Scenario\Fantasya\Model\Rumour;
use Lemuria\Scenario\Fantasya\Script\Act\Hearsay;

/**
 * This event passes hostilities to interested hearsayers.
 */
final class CollectRumour extends AbstractEvent
{
	/**
	 * @var array<string>
	 */
	private const array FEE_BUILDING = [Market::class, Port::class, Quay::class];

	/**
	 * @var array<string>
	 */
	private const array MONSTERS = [
		Ent::class, GiantFrog::class, Goblin::class, Sandworm::class, Skeleton::class, Wolf::class, Zombie::class
	];

	/**
	 * @var array<Hearsay>
	 */
	private static array $hearsayers = [];

	/**
	 * @var array<Rumour>
	 */
	private static array $battle = [];

	/**
	 * @var array<Rumour>
	 */
	private static array $encounter = [];

	/**
	 * @var array<Rumour>
	 */
	private static array $fee = [];

	/**
	 * @var array<Rumour>
	 */
	private static array $market = [];

	/**
	 * @var array<Rumour>
	 */
	private static array $monster = [];

	/**
	 * @var array<string, true>
	 */
	private array $monsterRaces;

	/**
	 * @var array<int, array>
	 */
	private array $cache;

	public static function register(Hearsay $hearsay): void {
		$interest = $hearsay->Interest();
		foreach ($interest as $rumour) {
			switch ($rumour->Myth()) {
				case Myth::Battle :
					self::$battle[] = $rumour;
					break;
				case Myth::Encounter :
					self::$encounter[] = $rumour;
					break;
				case Myth::Fee :
					self::$fee[] = $rumour;
					break;
				case Myth::Market :
					self::$market[] = $rumour;
					break;
				case Myth::Monster :
					self::$monster[] = $rumour;
					break;
			}
		}
		if (!empty($interest)) {
			self::$hearsayers[] = $hearsay;
		}
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::After);
		$this->monsterRaces = array_fill_keys(self::MONSTERS, true);
	}

	protected function run(): void {
		foreach (self::$hearsayers as $hearsay) {
			$hearsay->update();
		}

		$this->appendBattleIncidents();

		$this->getEncounterLocations();
		$this->appendEncounters();

		$this->getConstructionsWithFee();
		$this->appendFees();
		$this->appendMarkets();

		$this->appendMonsterSightings();

		foreach (self::$hearsayers as $hearsay) {
			$hearsay->collect();
		}
	}

	protected function appendBattleIncidents(): void {
		$this->cache = [];
		foreach (self::$battle as $rumour) {
			foreach ($rumour->Area() as $region) {
				$battles = $this->getBattlesIn($region);
				foreach ($battles as $battle) {
					$rumour->Incidents()->append($battle);
				}
			}
		}
	}

	private function appendEncounters(): void {
		foreach (self::$encounter as $rumour) {
			foreach ($rumour->Area() as $region) {
				$id = $region->Id()->Id();
				if (isset($this->cache[$id])) {
					foreach ($this->cache[$id] as $unit) {
						$rumour->Incidents()->append($unit);
					}
				}
			}
		}
	}

	private function appendFees(): void {
		foreach (self::$fee as $rumour) {
			foreach ($rumour->Area() as $region) {
				$id = $region->Id()->Id();
				if (isset($this->cache[$id])) {
					foreach ($this->cache[$id] as $constructions) {
						$rumour->Incidents()->append($constructions);
					}
				}
			}
		}
	}

	private function appendMarkets(): void {
		foreach (self::$market as $rumour) {
			foreach ($rumour->Area() as $region) {
				$id = $region->Id()->Id();
				if (isset($this->cache[$id]['Market'])) {
					foreach ($this->cache[$id]['Market'] as $constructions) {
						$rumour->Incidents()->append($constructions);
					}
				}
			}
		}
	}

	private function appendMonsterSightings(): void {
		$this->cache = [];
		foreach (self::$monster as $rumour) {
			foreach ($rumour->Area() as $region) {
				$monsters = $this->getMonstersIn($region);
				foreach ($monsters as $unit) {
					$rumour->Incidents()->append($unit);
				}
				if ($region->Id() == '2ex' && $rumour->Incidents()->count() === 0) {
					$rumour->Incidents()->append(Unit::get(Id::fromId('1gs')));
				}
			}
		}
	}

	/**
	 * @return array<Battle>
	 */
	private function getBattlesIn(Region $region): array {
		$id = $region->Id()->Id();
		if (!isset($this->cache[$id])) {
			$this->cache[$id] = Lemuria::Hostilities()->findAll($region);
		}
		return $this->cache[$id];
	}

	/**
	 * @return array<Unit>
	 */
	private function getMonstersIn(Region $region): array {
		$id = $region->Id()->Id();
		if (!isset($this->cache[$id])) {
			foreach ($region->Residents() as $unit) {
				if ($unit->Party()->Type() === Type::Monster) {
					if (isset($this->monsterRaces[$unit->Race()::class])) {
						$this->cache[$id][] = $unit;
					}
				}
			}
		}
		return $this->cache[$id] ?? [];
	}

	private function getEncounterLocations(): void {
		$this->cache = [];
		$party       = $this->state->getTurnOptions()->Finder()->Party()->findByType(Type::NPC);
		foreach ($party->People() as $unit) {
			$region = $unit->Region()->Id()->Id();
			$this->cache[$region][] = $unit;
		}
	}

	private function getConstructionsWithFee(): void {
		$this->cache = [];
		$withFee     = [];
		foreach (self::FEE_BUILDING as $building) {
			$withFee[getClass($building)] = true;
		}
		foreach (Construction::all() as $construction) {
			$building = getClass($construction->Building());
			if (isset($withFee[$building])) {
				$region                            = $construction->Region()->Id()->Id();
				$this->cache[$region][$building][] = $construction;
			}
		}
	}
}
