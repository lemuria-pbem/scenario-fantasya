<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest\Controller;

use Lemuria\Engine\Fantasya\Census;
use Lemuria\Engine\Fantasya\Factory\FollowTrait;
use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Message\Unit\LeaveConstructionMessage;
use Lemuria\Engine\Fantasya\Message\Unit\LeaveVesselMessage;
use Lemuria\Engine\Fantasya\Message\Unit\QuestCompletedMessage;
use Lemuria\Engine\Fantasya\Message\Unit\QuestFinishedMessage;
use Lemuria\Engine\Fantasya\Outlook;
use Lemuria\Id;
use Lemuria\Identifiable;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Reassignment;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;

class Instructor extends AbstractController implements Reassignment
{
	use BuilderTrait;
	use FollowTrait;
	use MessageTrait;

	private const string KNOWLEDGE = 'knowledge';

	private const string LEADER = 'leader';

	public function Knowledge(): Knowledge {
		$knowledge = new Knowledge();
		return $knowledge->unserialize($this->getFromPayload(self::KNOWLEDGE));
	}

	public function Leader(): ?Unit {
		$id = $this->getFromPayload(self::LEADER);
		return $id ? Unit::get(new Id($id)) : null;
	}

	public function createPayload(): Payload {
		$payload               = parent::createPayload();
		$payload[self::LEADER] = null;
		return $payload;
	}

	public function isAvailableFor(Party|Unit $subject): bool {
		$leader = $this->Leader();
		if ($leader) {
			$party = $subject instanceof Party ? $subject : $subject->Party();
			return $leader->Party() === $party;
		}
		return true;
	}

	public function isAssignedTo(Unit $unit): bool {
		return $unit === $this->Leader();
	}

	public function reassign(Id $oldId, Identifiable $identifiable): void {
		if ($identifiable->Catalog() === Domain::Unit) {
			$id = $this->getFromPayload(self::LEADER);
			if ($oldId->Id() === $id) {
				$this->payload()->offsetSet(self::LEADER, $identifiable->Id()->Id());
			}
		}
	}

	public function remove(Identifiable $identifiable): void {
		if ($identifiable->Catalog() === Domain::Unit) {
			$leader = $this->Leader();
			if ($leader && $identifiable === $leader) {
				$party    = $leader->Party();
				$follower = $this->quest()->Owner();
				$outlook  = new Outlook(new Census($follower->Party()));
				foreach ($outlook->getApparitions($leader->Region()) as $unit) {
					if ($unit !== $leader && $unit->Party() === $party) {
						$this->setLeader($unit);
						return;
					}
				}
				$this->payload()->offsetSet(self::LEADER, null);
				$this->ceaseFollowing($this->getExistingFollower($follower), $follower);
				Lemuria::Catalog()->remove($this->quest());
			}
		}
	}

	public function setKnowledge(Knowledge $knowledge): static {
		$this->payload()->offsetSet(self::KNOWLEDGE, $knowledge->serialize());
		return $this;
	}

	public function setLeader(Unit $leader): static {
		$this->payload()->offsetSet(self::LEADER, $leader->Id()->Id());
		return $this;
	}

	protected function updateStatus(): void {
		//TODO Cancel FOLGEN
		$quest         = $this->quest();
		$currentLeader = $this->Leader();
		$follower      = $this->getUnfollower();
		$this->leaveWhenDone($follower);
		$this->removeQuest($currentLeader);
		$this->message(QuestFinishedMessage::class, $currentLeader)->e($quest);
		if ($this->unit === $currentLeader) {
			$this->setStatus(Status::Completed);
			$this->deleteQuest($quest);
			$this->message(QuestFinishedMessage::class, $currentLeader)->e($quest);
			Lemuria::Log()->debug($follower . ' has terminated teaching ' . $currentLeader . '.');
		} else {
			Lemuria::Log()->debug('Instructor ' . $follower . ' changes leader from ' . $currentLeader . ' to ' . $this->unit . '.');
			$this->checkForAssign();
		}
	}

	protected function checkForAssign(): bool {
		//TODO handle payment
		$this->setLeader($this->unit);
		$this->assignQuest($this->unit);
		$follower = $this->getUnfollower();
		$this->enterForLeader($this->unit, $follower);
		$this->startFollowing($this->unit, $follower);
		Lemuria::Log()->debug($this->unit . ' has engaged ' . $this->quest()->Owner() . ' as teacher.');
		return true;
	}

	protected function checkForFinish(): bool {
		return false;
	}

	protected function completed(): void {
		$this->message(QuestCompletedMessage::class, $this->unit)->e($this->quest());
	}

	private function leaveWhenDone(Unit $unit): void {
		$construction = $unit->Construction();
		if ($construction) {
			$inhabitants = $construction->Inhabitants();
			if ($inhabitants->Owner()->Party() !== $unit->Party()) {
				$inhabitants->remove($unit);
				$this->message(LeaveConstructionMessage::class, $unit)->e($construction);
			}
		} else {
			$vessel= $unit->Vessel();
			if ($vessel) {
				$passengers = $vessel->Passengers();
				if ($passengers->Owner()->Party() !== $unit->Party()) {
					$passengers->remove($unit);
					$this->message(LeaveVesselMessage::class, $unit)->e($vessel);
				}
			}
		}
	}
}
