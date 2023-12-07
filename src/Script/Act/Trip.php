<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Lemuria;
use Lemuria\Model\World\Strategy\ShortestPath;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\TripTrait;

class Trip extends AbstractAct
{
	use TripTrait;

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->parseDestination($macro->getParameter());
		return $this;
	}

	public function play(): static {
		if ($this->hasReachedDestination()) {
			return $this;
		}
		if ($this->unit->Vessel()) {
			Lemuria::Log()->debug('Trip act: ' . $this->unit . ' is awaiting transport to ' . $this->destination . '.');
			return $this;
		}

		$path = $this->findWay(ShortestPath::class);
		if ($path->isViable()) {
			$this->leaveConstruction();
			$this->travel($path->getBest());
		} else {
			Lemuria::Log()->error('There is no viable path from ' . $this->start . ' to ' . $this->destination . '.');
		}

		return $this->addToChain();
	}

	public function getChainResult(): bool {
		return $this->scene->context()->Unit()->Region() !== $this->destination;
	}
}
