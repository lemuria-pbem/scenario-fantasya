<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Engine\Fantasya\Command\Travel;
use Lemuria\Engine\Fantasya\Command\Vacate\Leave;
use Lemuria\Engine\Fantasya\Factory\DirectionList;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\World\PathStrategy;
use Lemuria\Model\World\Way;

trait TripTrait
{
	private ?Region $start = null;

	private Region $destination;

	protected function leaveConstruction(): void {
		$this->setStartFromUnit();
		if ($this->unit->Construction()) {
			$leave = new Leave(new Phrase('VERLASSEN'), $this->scene->context());
			State::getInstance()->injectIntoTurn($leave);
		}
	}

	protected function travel(Way $way): int {
		$context = $this->scene->context();
		$list    = DirectionList::fromWay($way, $context);
		$n       = $list->count();
		if ($n > 0) {
			$directions = implode(' ', $list->route());
			$travel     = new Travel(new Phrase('REISEN ' . $directions), $context);
			State::getInstance()->injectIntoTurn($travel->preventDefault());
		}
		return $n;
	}

	protected function parseDestination(string $id): Region {
		$this->destination = Region::get(Id::fromId($id));
		return $this->destination;
	}

	protected function setStartFromUnit(): void {
		if (!$this->start) {
			$this->start = $this->unit->Region();
		}
	}

	protected function hasReachedDestination(): bool {
		$this->setStartFromUnit();
		return $this->start === $this->destination;
	}

	protected function findWay(string $pathStrategy): PathStrategy {
		$this->setStartFromUnit();
		return Lemuria::World()->findPath($this->start, $this->destination, $pathStrategy);
	}
}
