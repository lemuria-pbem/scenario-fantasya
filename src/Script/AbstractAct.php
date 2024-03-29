<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Act;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\ScenarioOrders;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;

abstract class AbstractAct implements Act
{
	protected Macro $macro;

	protected Unit $unit;

	public function __construct(protected readonly AbstractScene $scene) {
		$this->unit = $scene->context()->Unit();
	}

	public function Unit(): Unit {
		return $this->unit;
	}

	public function parse(Macro $macro): static {
		$this->macro = $macro;
		return $this;
	}

	public function play(): static {
		Lemuria::Log()->debug('Playing act ' . $this->macro . '.');
		return $this;
	}

	public function prepareNext(): static {
		$macro = (string)$this->macro;
		if ($this->includeInNext()) {
			$this->scene->Section()->Lines()->add($macro);
		}
		$this->addToOrders($macro);
		return $this;
	}

	protected function addToChain(): static {
		/** @var SetOrders $scene */
		$scene = $this->scene;
		$scene->chain($this);
		return $this;
	}

	protected function includeInNext(): bool {
		return true;
	}

	private function addToOrders(string $macro): void {
		$orders = Lemuria::Orders();
		if ($orders instanceof ScenarioOrders) {
			$orders->getScenario($this->unit->Id())[] = $macro;
		}
	}
}
