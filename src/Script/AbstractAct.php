<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Scenario\Fantasya\Act;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;

abstract class AbstractAct implements Act
{
	protected readonly Macro $macro;

	public function __construct(protected readonly AbstractScene $scene) {
	}

	public function parse(Macro $macro): static {
		$this->macro = $macro;
		return $this;
	}

	public function prepareNext(): static {
		$this->scene->Section()->Lines()->add((string)$this->macro);
		return $this;
	}

	protected function addToChain(): static {
		/** @var SetOrders $scene */
		$scene = $this->scene;
		$scene->chain($this);
		return $this;
	}
}
