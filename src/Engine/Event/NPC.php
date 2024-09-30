<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Extension\QuestsWithPerson;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;

/**
 * This event prepares the NPCs' turn.
 */
final class NPC extends AbstractEvent
{
	public function __construct(State $state) {
		parent::__construct($state, Priority::Before);
	}

	protected function run(): void {
		$count = 0;
		foreach (Party::all() as $party) {
			if ($party->Type() === Type::Player) {
				$this->clearUnassignedQuests($party);
			}
			if ($party->Type() === Type::NPC) {
				$count += $this->countUnits($party);
			}
		}
		Lemuria::Log()->debug('Turn for ' . $count . ' NPC units has been added.');
	}

	private function clearUnassignedQuests(Party $party): void {
		$extensions = $party->Extensions();
		if ($extensions->offsetExists(QuestsWithPerson::class)) {
			$remove = [];
			/** @var QuestsWithPerson $quests */
			$quests = $extensions->offsetGet(QuestsWithPerson::class);
			foreach ($quests as $quest) {
				/** @var Payload $payload */
				$payload = $quest->Payload();
				if ($payload->status($party) === Status::None) {
					$remove[] = $quest;
				}
			}
			if (count($remove) >= $quests->count()) {
				$extensions->offsetUnset(QuestsWithPerson::class);
			} else {
				foreach ($remove as $quest) {
					$quests->remove($quest);
				}
			}
		}
	}

	private function countUnits(Party $party): int {
		$count = 0;
		foreach ($party->People()->getClone() as $unit) {
			if ($unit->Size() > 0) {
				$count++;
			}
		}
		return $count;
	}
}
