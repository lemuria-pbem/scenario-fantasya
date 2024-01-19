<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Engine\Event;

use Lemuria\Engine\Fantasya\Event\AbstractEvent;
use Lemuria\Engine\Fantasya\Priority;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Scenario\Fantasya\Script\Act\Merchant;

/**
 * This event calls registered merchants.
 */
final class FinishMerchants extends AbstractEvent
{
	/**
	 * @var array<Merchant>
	 */
	private static array $merchants = [];

	public static function register(Merchant $merchant): void {
		self::$merchants[] = $merchant;
	}

	public function __construct(State $state) {
		parent::__construct($state, Priority::After);
	}

	protected function run(): void {
		foreach (self::$merchants as $merchant) {
			$merchant->finish();
		}
	}
}
