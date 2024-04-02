<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Command\UnitCommand;
use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Unit;

/**
 * Planned trips are injected.
 */
final class TravelCommands extends AbstractEvent
{
	/**
	 * @var array<int, array<UnitCommand>>
	 */
	private static array $commands = [];

	/**
	 * @var array<int, true>
	 */
	private static array $enforce = [];

	public static function add(UnitCommand $command): void {
		$unit = $command->Unit();
		$id   = $unit->Id()->Id();
		if (isset(self::$commands[$id])) {
			self::$commands[$id][] = $command;
		} else {
			Lemuria::Log()->debug($unit . ' plans a trip.');
			self::$commands[$id] = [$command];
		}
	}

	public static function cancelTravelFor(Unit $unit): void {
		$id = $unit->Id()->Id();
		if (isset(self::$enforce[$id])) {
			Lemuria::Log()->debug('The trip of ' . $unit . ' cannot be cancelled.');
		} else {
			self::$enforce[$id] = false;
			unset(self::$commands[$id]);
			Lemuria::Log()->debug('The trip of ' . $unit . ' has been cancelled.');
		}
	}

	public static function enforceTravelFor(Unit $unit): void {
		self::$enforce[$unit->Id()->Id()] = true;
		Lemuria::Log()->debug($unit . ' will definitely make its trip.');
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::Middle);
	}

	protected function run(): void {
		foreach (self::$commands as $id => $commands) {
			$unit = Unit::get(new Id($id));
			if (array_key_exists($id, self::$enforce) && !self::$enforce[$id]) {
				Lemuria::Log()->debug('The trip of ' . $unit . ' has been cancelled.');
			} else {
				Lemuria::Log()->debug('The trip of ' . $unit . ' has been confirmed.');
				foreach ($commands as $command) {
					$this->state->injectIntoTurn($command);
				}
			}
		}
	}
}
