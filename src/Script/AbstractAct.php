<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Act;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\ScenarioOrders;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;

abstract class AbstractAct implements Act
{
	protected Macro $macro;

	public function __construct(protected readonly AbstractScene $scene) {
	}

	public function parse(Macro $macro): static {
		$this->macro = $macro;
		return $this;
	}

	public function prepareNext(): static {
		$macro = (string)$this->macro;
		$this->scene->Section()->Lines()->add($macro);
		$this->addToOrders($macro);
		return $this;
	}

	protected function addToChain(): static {
		/** @var SetOrders $scene */
		$scene = $this->scene;
		$scene->chain($this);
		return $this;
	}

	private function addToOrders(string $macro): void {
		$orders = Lemuria::Orders();
		if ($orders instanceof ScenarioOrders) {
			$orders->getScenario($this->scene->context()->Unit()->Id())[] = $macro;
		}
	}
}
