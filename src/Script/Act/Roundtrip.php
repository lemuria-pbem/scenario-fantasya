<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

class Roundtrip extends Trip
{
	protected function startTrip(): bool {
		$this->addToChain();
		if ($this->hasReachedDestination()) {
			$destinations   = $this->macro->getParameters();
			$first          = array_shift($destinations);
			$destinations[] = $first;
			$this->macro->setParameters($destinations);
			return false;
		}
		return true;
	}

	protected function endTrip(): static {
		return $this;
	}
}
