<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Engine\Event\TravelCommands;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\SingletonSet;
use Lemuria\Storage\Ini\Values;

/**
 * Act: Ankauf(…)
 */
class Demand extends AbstractAct
{
	use VisitationTrait;

	private const string WAIT = 'WartenAufAntwort';

	/**
	 * @var array<int, self>
	 */
	private static array $demand = [];

	private Values $values;

	private SingletonSet $compositions;

	private bool $isWaiting = false;

	public static function getInstance(Unit $unit): ?self {
		$id = $unit->Id()->Id();
		return self::$demand[$id] ?? null;
	}

	public function __construct(SetOrders $scene) {
		parent::__construct($scene);
		$id                = $this->unit->Id()->Id();
		self::$demand[$id] = $this;
		$this->values      = $scene->Section()->Values();
	}

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->compositions = new SingletonSet();
		$factory            = $this->scene->context()->Factory();
		foreach ($macro->getParameters() as $parameter) {
			if ($factory->isComposition($parameter)) {
				$composition = $factory->composition($parameter);
				$this->compositions->add($composition);
				Lemuria::Log()->debug('Unit ' . $this->unit . ' will make offers for ' . $composition . '.');
			} else {
				Lemuria::Log()->critical('Parameter ' . $parameter . ' is no Composition.');
			}
		}
		if ($this->compositions->isEmpty()) {
			$this->compositions->fill($this->getDefaultDemand());
			Lemuria::Log()->debug('Unit ' . $this->unit . ' will make offers for any unicum.');
		}
		if ($this->values->offsetExists(self::WAIT)) {
			$this->isWaiting = true;
			$this->values->offsetUnset(self::WAIT);
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$this->addVisitationEffect()->Demand()->fill($this->compositions);
		if ($this->isWaiting) {
			TravelCommands::enforceTravelFor($this->unit);
		}
		return $this;
	}

	public function getChainResult(): bool {
		return true;
	}

	public function waitForAcceptance(): bool {
		if ($this->isWaiting) {
			Lemuria::Log()->debug($this->unit . ' will not wait any more.');
			return false;
		}
		$this->values[self::WAIT] = '1';
		Lemuria::Log()->debug($this->unit . ' will wait until next round.');
		return true;
	}
}
