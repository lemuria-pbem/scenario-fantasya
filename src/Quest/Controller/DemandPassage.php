<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest\Controller;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Message\Unit\BoardMessage;
use Lemuria\Engine\Fantasya\Message\Unit\GiveMessage;
use Lemuria\Engine\Fantasya\Message\Unit\GiveReceivedFromForeignMessage;
use Lemuria\Engine\Fantasya\Message\Unit\LeaveVesselMessage;
use Lemuria\Engine\Fantasya\Message\Unit\QuestCompletedMessage;
use Lemuria\Engine\Fantasya\Message\Unit\QuestFinishedMessage;
use Lemuria\Id;
use Lemuria\Identifiable;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Relation;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\Scenario\Payload;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Reassignment;
use Lemuria\Scenario\Fantasya\Quest\Status;

class DemandPassage extends AbstractController implements Reassignment
{
	use BuilderTrait;
	use MessageTrait;

	private const string DESTINATION = 'destination';

	private const string PAYMENT = 'payment';

	private const string CAPTAIN = 'captain';

	public function Destination(): Region {
		return Region::get(new Id((int)$this->getFromPayload(self::DESTINATION)));
	}

	public function Payment(): Resources {
		$payment = new Resources();
		return $payment->unserialize($this->getFromPayload(self::PAYMENT));
	}

	public function Captain(): ?Unit {
		$id = $this->getFromPayload(self::CAPTAIN);
		return $id ? Unit::get(new Id($id)) : null;
	}

	public function createPayload(): Payload {
		$payload = parent::createPayload();
		$payload[self::CAPTAIN] = null;
		return $payload;
	}

	public function isAvailableFor(Party|Unit $subject): bool {
		return !$this->Captain();
	}

	public function isAssignedTo(Unit $unit): bool {
		return $unit === $this->Captain() && parent::isAssignedTo($unit);
	}

	public function reassign(Id $oldId, Identifiable $identifiable): void {
		if ($identifiable->Catalog() === Domain::Unit) {
			$id = $this->getFromPayload(self::CAPTAIN);
			if ($oldId->Id() === $id) {
				$this->payload()->offsetSet(self::CAPTAIN, $identifiable->Id()->Id());
			}
		}
	}

	public function remove(Identifiable $identifiable): void {
		if ($identifiable->Catalog() === Domain::Unit) {
			$captain = $this->Captain();
			if ($captain && $identifiable === $captain) {
				$passengers = $captain->Vessel()?->Passengers();
				if ($passengers) {
					$me      = $this->quest()->Owner();
					$captain = null;
					foreach ($passengers as $unit) {
						if ($unit !== $me && $unit !== $identifiable) {
							$captain = $unit;
							break;
						}
					}
					if ($captain) {
						$this->setCaptain($captain);
						Lemuria::Log()->debug('New captain ' . $captain . ' set.');
					} else {

						Lemuria::Log()->warning('Captain killed, there is no replacement!');
					}
				}
			}
		}
	}

	public function setDestination(Region $region): static {
		$this->payload()->offsetSet(self::DESTINATION, $region->Id()->Id());
		return $this;
	}

	public function setPayment(Resources $resources): static {
		$this->payload()->offsetSet(self::PAYMENT, $resources->serialize());
		return $this;
	}

	public function setCaptain(Unit $unit): static {
		$this->payload()->offsetSet(self::CAPTAIN, $unit->Id()->Id());
		Lemuria::Catalog()->addReassignment($this);
		return $this;
	}

	protected function updateStatus(): void {
		$this->setStatus(Status::Completed);
		$quest  = $this->quest();
		$unit   = $quest->Owner();
		$vessel = $unit->Vessel();
		$vessel->Passengers()->remove($unit);
		$this->message(LeaveVesselMessage::class, $unit)->e($vessel);
		$this->removeQuest($this->unit);
		$this->deleteQuest($quest);
		$this->message(QuestFinishedMessage::class, $this->unit)->e($quest);
		Lemuria::Log()->debug($unit . ' has been transported to ' . $this->Destination() . '.');
	}

	protected function checkForAssign(): bool {
		$vessel = $this->unit->Vessel();
		if (!$vessel || $this->unit !== $vessel->Passengers()->Owner()) {
			Lemuria::Log()->error($this->unit . ' is not a captain.');
			return false;
		}

		$passenger = $this->quest()->Owner();
		if ($passenger->Construction() || $passenger->Vessel()) {
			Lemuria::Log()->error($passenger . ' is not ready to board.');
			return false;
		}
		if (!$this->unit->Party()->Diplomacy()->has(Relation::ENTER, $passenger)) {
			Lemuria::Log()->error($passenger . ' is not allowed to board the vessel ' . $vessel . '.');
			return false;
		}

		$inventory = $passenger->Inventory();
		$canPay    = true;
		foreach ($this->Payment() as $quantity) {
			if ($inventory->offsetGet($quantity->Commodity())->Count() < $quantity->Count()) {
				Lemuria::Log()->error($passenger . ' cannot pay ' . $quantity . '.');
				$canPay = false;
			}
		}
		if (!$canPay) {
			return false;
		}

		$vessel->Passengers()->add($passenger);
		$this->message(BoardMessage::class, $passenger)->e($vessel);
		foreach ($this->Payment() as $quantity) {
			$inventory->remove(new Quantity($quantity->Commodity(), $quantity->Count()));
			$this->unit->Inventory()->add(new Quantity($quantity->Commodity(), $quantity->Count()));
			$this->message(GiveMessage::class, $passenger)->i($quantity)->e($this->unit);
			$this->message(GiveReceivedFromForeignMessage::class, $this->unit)->i($quantity)->e($passenger);
		}
		$this->setCaptain($this->unit);
		$this->assignQuest($this->unit);
		Lemuria::Log()->debug($passenger . ' has boarded vessel ' . $vessel . ' and paid the passage.');
		return true;
	}

	protected function checkForFinish(): bool {
		return $this->unit->Region() === $this->Destination();
	}

	protected function completed(): void {
		$this->message(QuestCompletedMessage::class, $this->unit)->e($this->quest());
	}
}
