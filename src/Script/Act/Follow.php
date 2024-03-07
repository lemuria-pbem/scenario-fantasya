<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Command\Trespass\Board;
use Lemuria\Engine\Fantasya\Command\Trespass\Enter;
use Lemuria\Engine\Fantasya\Command\Vacate\Leave;
use Lemuria\Engine\Fantasya\Effect\FollowEffect;
use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Factory\SiegeTrait;
use Lemuria\Engine\Fantasya\Message\Unit\FollowerMessage;
use Lemuria\Engine\Fantasya\Message\Unit\FollowerNotMessage;
use Lemuria\Engine\Fantasya\Message\Unit\FollowingMessage;
use Lemuria\Engine\Fantasya\Message\Unit\FollowingNotMessage;
use Lemuria\Engine\Fantasya\Message\Unit\LeaveConstructionMessage;
use Lemuria\Engine\Fantasya\Message\Unit\LeaveVesselMessage;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Extension\Followers;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;

/**
 * Act: Folgen([ID, [N]])
 */
class Follow extends AbstractAct
{
	use MessageTrait;
	use SiegeTrait;

	private ?Unit $leader = null;

	private ?int $maxRounds = null;

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$leader = $macro->getParameter();
		if ($leader) {
			$this->leader = Unit::get(Id::fromId($macro->getParameter()));
		}
		$parameter = $macro->getParameter(2);
		$maxRounds = (int)$parameter;
		if ((string)$maxRounds === $parameter) {
			$this->maxRounds = $maxRounds;
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		if ($this->leader && ($this->maxRounds === null || $this->maxRounds > 0)) {
			if ($this->maxRounds > 0) {
				$this->macro->setParameters([(string)$this->leader->Id(), --$this->maxRounds]);
			}
			return $this->follow();
		}
		return $this->unfollow();
	}

	public function getChainResult(): bool {
		return !$this->leader;
	}

	protected function includeInNext(): bool {
		return (bool)$this->leader;
	}

	protected function follow(): static {
		$follow = $this->getExistingFollower();
		if ($follow) {
			if ($follow->Leader() === $this->leader) {
				/** @var Followers $followers */
				$followers = $this->leader->Extensions()->init(Followers::class);
				$followers->Followers()->add($this->Unit());
			} else {
				$this->ceaseFollowing($follow);
				$this->startFollowing();
			}
		} else {
			$this->startFollowing();
		}
		$this->assertSameEnvironment();
		return $this->addToChain();
	}

	protected function unfollow(): static {
		$follow = $this->getExistingFollower();
		if ($follow) {
			$this->ceaseFollowing($follow);
		}
		return $this;
	}

	protected function assertSameEnvironment(): void {
		$follower     = $this->Unit();
		$construction = $this->leader->Construction();
		$vessel       = $this->leader->Vessel();
		if ($construction) {
			$currentConstruction = $follower->Construction();
			$currentVessel       = $follower->Vessel();
			if ($currentConstruction) {
				if ($currentConstruction !== $construction && $this->canEnterOrLeave($follower)) {
					$currentConstruction->Inhabitants()->remove($follower);
					$this->message(LeaveConstructionMessage::class, $follower)->e($currentConstruction);
					$this->addEnterCommand($construction);
				}
			} elseif ($currentVessel) {
				$currentVessel->Passengers()->remove($follower);
				$this->message(LeaveVesselMessage::class, $follower)->e($currentVessel);
				$this->addEnterCommand($construction);
			} else {
				$this->addEnterCommand($construction);
			}
		} elseif ($vessel) {
			$currentConstruction = $follower->Construction();
			$currentVessel       = $follower->Vessel();
			if ($currentConstruction && $this->canEnterOrLeave($follower)) {
				$currentConstruction->Inhabitants()->remove($follower);
				$this->message(LeaveConstructionMessage::class, $follower)->e($currentConstruction);
				$this->addBoardCommand($vessel);
			} elseif ($currentVessel) {
				if ($currentVessel !== $vessel) {
					$currentVessel->Passengers()->remove($follower);
					$this->message(LeaveVesselMessage::class, $follower)->e($currentVessel);
					$this->addBoardCommand($vessel);
				}
			} else {
				$this->addBoardCommand($vessel);
			}
		} else {
			if ($follower->Construction() || $follower->Vessel()) {
				$this->addLeaveCommand();
			}
		}
	}

	private function getExistingFollower(): ?FollowEffect {
		$follower = $this->Unit();
		$follow   = new FollowEffect(State::getInstance());
		$follow   = Lemuria::Score()->find($follow->setUnit($follower));
		return $follow instanceof FollowEffect ? $follow : null;
	}

	private function startFollowing(): void {
		$follower = $this->Unit();
		$follow   = new FollowEffect(State::getInstance());
		Lemuria::Score()->add($follow->setUnit($follower)->setLeader($this->leader));
		Lemuria::Log()->debug($follower . ' will follow ' . $this->leader . ' from now on.');
		/** @var Followers $followers */
		$followers = $this->leader->Extensions()->init(Followers::class);
		$followers->Followers()->add($follower);
		$this->message(FollowerMessage::class, $follower)->e($this->leader);
		$this->message(FollowingMessage::class, $this->leader)->e($follower);
	}

	private function ceaseFollowing(FollowEffect $follow): void {
		$follower   = $this->Unit();
		$leader     = $follow->Leader();
		$extensions = $leader->Extensions();
		if (isset($extensions[Followers::class])) {
			/** @var Followers $followers */
			$followers = $extensions[Followers::class];
			if ($followers->Followers()->has($follower->Id())) {
				$followers->Followers()->remove($follower);
				$this->message(FollowingNotMessage::class, $follower)->e($leader);
				$this->message(FollowerNotMessage::class, $leader)->e($follower);
			}
		}
		Lemuria::Score()->remove($follow);
		Lemuria::Log()->debug($follower . ' will not follow ' . $leader . ' any longer.');
	}

	private function addEnterCommand(Construction $construction): void {
		$enter = new Enter(new Phrase('BETRETEN ' . $construction->Id()), $this->scene->context());
		State::getInstance()->injectIntoTurn($enter);
	}

	private function addBoardCommand(Vessel $vessel): void {
		$board = new Board(new Phrase('BESTEIGEN ' . $vessel->Id()), $this->scene->context());
		State::getInstance()->injectIntoTurn($board);
	}

	private function addLeaveCommand(): void {
		$leave = new Leave(new Phrase('VERLASSEN'), $this->scene->context());
		State::getInstance()->injectIntoTurn($leave);
	}
}
