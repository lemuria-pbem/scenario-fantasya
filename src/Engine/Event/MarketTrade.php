<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Effect\TradeEffect;
use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Extension\Trades;
use Lemuria\Scenario\Fantasya\Script\Act\Market;

/**
 * This event passes NPCs' trades to the Market act.
 */
final class MarketTrade extends AbstractEvent
{
	/**
	 * @var array<Market>
	 */
	private static array $acts = [];

	public static function register(Market $market): void {
		self::$acts[] = $market;
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::Middle);
	}

	protected function run(): void {
		$effect = new TradeEffect(State::getInstance());
		foreach (self::$acts as $market) {
			$existing = Lemuria::Score()->find($effect->setUnit($market->Unit()));
			$trades   = $existing instanceof TradeEffect ? $existing->Trades() : new Trades();
			$market->setTrades($trades);
		}
	}
}
