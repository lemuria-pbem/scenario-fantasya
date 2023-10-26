<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Event\DelegatedEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;

/**
 * This event prepares the NPCs' turn.
 */
final class DelegatedScenario extends DelegatedEvent
{
	public function __construct(State $state) {
		parent::__construct($state, Priority::Before);
	}

	public function getDelegates(): array {
		Lemuria::Log()->debug('Adding scenario delegates.');
		return parent::getDelegates();
	}

	protected function createDelegates(): void {
		$this->delegates[] = new NPC($this->state);
	}
}
