<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Extension\Quests;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;

/**
 * This event removes quests whose TTL has expired.
 */
final class DeadQuests extends AbstractEvent
{
	public function __construct(State $state) {
		parent::__construct($state, Priority::Middle);
	}

	protected function run(): void {
		foreach (Quest::all() as $quest) {
			/** @var Payload $payload */
			$payload = $quest->Payload();
			if ($payload->hasAnyStatus(Status::Assigned)) {
				continue;
			}
			$ttl = $payload->ttl();
			if ($payload->ttl() !== Payload::IMMORTAL) {
				if ($ttl <= 0) {
					$extensions = $quest->Owner()->Extensions();
					/** @var Quests $quests */
					$quests = $extensions[Quests::class];
					$quests->remove($quest);
					Lemuria::Catalog()->remove($quest);
					Lemuria::Log()->debug('Quest ' . $quest . ' has been removed.');
				} else {
					$payload->setTtl(--$ttl);
					Lemuria::Log()->debug('Quest ' . $quest . ' TTL is now ' . $ttl . '.');
				}
			}
		}
	}
}
