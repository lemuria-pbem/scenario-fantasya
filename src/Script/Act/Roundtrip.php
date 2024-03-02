<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

/**
 * Act: Rundreise(…)
 */
class Roundtrip extends Trip
{
	protected function startTrip(): bool {
		if ($this->hasReachedDestination()) {
			$destinations   = $this->macro->getParameters();
			$first          = array_shift($destinations);
			$destinations[] = $first;
			$this->macro->setParameters($destinations);
			$this->parseDestination($destinations[0]);
		}
		$this->addToChain();
		return true;
	}

	protected function endTrip(): static {
		return $this;
	}
}
