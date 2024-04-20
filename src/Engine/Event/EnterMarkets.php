<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Effect\TravelEffect;
use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Script\Act\Market;
use Lemuria\Scenario\Fantasya\Script\Act\Trip;

/**
 * Merchants immediately enter markets after having done the trip.
 */
final class EnterMarkets extends AbstractEvent
{
	/**
	 * @var array<int, Market>
	 */
	private static array $market = [];

	/**
	 * @var array<int, Trip>
	 */
	private static array $trip = [];

	public static function register(Market|Trip $act): void {
		$id                = $act->Unit()->Id()->Id();
		if ($act instanceof Market) {
			self::$market[$id] = $act;
		} else {
			self::$trip[$id] = $act;
		}
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::After);
	}

	protected function run(): void {
		foreach (self::$market as $id => $market) {
			if (self::$trip[$id]) {
				/** @var Market $market */
				if ($this->hasTravelled($market->Unit())) {
					$market->enterAfterTrip();
				}
			}
		}
	}

	private function hasTravelled(Unit $unit): bool {
		$effect = new TravelEffect($this->state);
		return (bool)Lemuria::Score()->find($effect->setUnit($unit));
	}
}
