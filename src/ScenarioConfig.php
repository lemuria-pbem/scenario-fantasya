<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Orders;
use Lemuria\Engine\Fantasya\Storage\LemuriaConfig;

abstract class ScenarioConfig extends LemuriaConfig
{
	public function Orders(): Orders {
		return new ScenarioOrders();
	}
}
