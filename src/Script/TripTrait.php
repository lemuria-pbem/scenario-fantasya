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
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\World\PathStrategy;
use Lemuria\Model\World\Way;

trait TripTrait
{
	private ?Unit $unit = null;

	private Region $start;

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
			State::getInstance()->injectIntoTurn($travel);
		}
		return $n;
	}

	protected function parseDestination(string $id): void {
		$this->destination = Region::get(Id::fromId($id));
	}

	protected function setStartFromUnit(): void {
		if (!$this->unit) {
			$this->unit  = $this->scene->context()->Unit();
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
