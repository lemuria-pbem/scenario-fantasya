<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest\Controller;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Scenario\Payload as PayloadModel;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Model\Fantasya\Scenario\Quest\Controller;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Exception\OffsetNotFoundException;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;
use Lemuria\SingletonTrait;

abstract class AbstractController implements Controller
{
	use SingletonTrait;

	protected ?Quest $quest = null;

	protected ?PayloadModel $payload = null;

	protected Unit $unit;

	public function createPayload(): PayloadModel {
		return new Payload();
	}

	public function setPayload(Quest $quest): static {
		$this->quest   = $quest;
		$this->payload = $quest->Payload();
		return $this;
	}

	public function isAvailableFor(Party|Unit $subject): bool {
		return true;
	}

	public function canBeFinishedBy(Unit $unit): bool {
		return $this->isAssignedTo($unit) && $this->checkForFinish();
	}

	public function isAssignedTo(Unit $unit): bool {
		$this->unit = $unit;
		return $this->status() === Status::Assigned;
	}

	public function isCompletedBy(Unit $unit): bool {
		$this->unit = $unit;
		return $this->status() === Status::Completed;
	}

	public function callFrom(Unit $unit): static {
		$this->unit = $unit;
		switch ($this->status()) {
			case Status::None :
				if ($this->checkForAssign()) {
					$this->setStatus(Status::Assigned);
				}
				break;
			case Status::Assigned :
				$this->updateStatus();
				break;
			default :
				Lemuria::Log()->debug('Controller ' . $this . ' called for completed quest.');
				break;
		}
		return $this;
	}

	abstract protected function updateStatus(): void;

	abstract protected function checkForFinish(): bool;

	protected function checkForAssign(): bool {
		return true;
	}

	protected function quest(): Quest {
		if ($this->quest) {
			return $this->quest;
		}
		throw new LemuriaException('Quest has not been set.');
	}

	protected function payload(): Payload {
		if ($this->payload instanceof Payload) {
			return $this->payload;
		}
		throw new LemuriaException('Payload has not been set.');
	}

	protected function getFromPayload(string $offset): mixed {
		if ($this->payload()->offsetExists($offset)) {
			return $this->payload[$offset];
		}
		throw new OffsetNotFoundException($offset);
	}

	protected function status(): Status {
		return $this->payload()->status($this->unit->Party());
	}

	protected function setStatus(Status $status): void {
		$this->payload()->setStatus($this->unit->Party(), $status);
	}
}
