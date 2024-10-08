<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Travel\Movement;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\World\Strategy\OverLand;
use Lemuria\Model\World\Strategy\ShortestPath;
use Lemuria\Scenario\Fantasya\Engine\Event\EnterMarkets;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\TripTrait;

/**
 * Act: Reise(ID)
 */
class Trip extends AbstractAct
{
	use TripTrait;

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->parseDestination($macro->getParameter());
		return $this;
	}

	public function play(): static {
		parent::play();
		if (!$this->startTrip()) {
			return $this;
		}
		if (!$this->takeTrip()) {
			return $this;
		}
		return $this->endTrip();
	}

	public function getChainResult(): bool {
		return $this->unit->Region() !== $this->destination;
	}

	protected function includeInNext(): bool {
		return !$this->hasReachedDestination();
	}

	protected function startTrip(): bool {
		return !$this->hasReachedDestination();
	}

	protected function takeTrip(): bool {
		if ($this->unit->Vessel()) {
			Lemuria::Log()->debug('Trip act: ' . $this->unit . ' is awaiting transport to ' . $this->destination . '.');
			return false;
		}

		$movement = $this->scene->context()->getCalculus($this->unit)->getTrip()->Movement();
		if (in_array($movement, [Movement::Fly, Movement::Ship])) {
			$path = $this->findWay(ShortestPath::class);
		} else {
			$path = $this->findWay(OverLand::class);
		}

		if ($path->isViable()) {
			if ($this->travel($path->getBest()) > 0) {
				EnterMarkets::register($this);
			}
		} else {
			Lemuria::Log()->notice('There is no viable path from ' . $this->start . ' to ' . $this->destination . '.');
		}
		return true;
	}

	protected function endTrip(): static {
		return $this->addToChain();
	}
}
