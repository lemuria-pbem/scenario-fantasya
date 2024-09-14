<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Scenario\Fantasya\Script\Act\Teacher;

/**
 * This event passes NPCs' trades to the Market act.
 */
final class TeachingInstructors extends AbstractEvent
{
	/**
	 * @var array<Teacher>
	 */
	private static array $acts = [];

	public static function register(Teacher $teacher): void {
		self::$acts[] = $teacher;
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::Middle);
	}

	protected function run(): void {
		foreach (self::$acts as $teacher) {
			$teacher->teach();
		}
	}
}
