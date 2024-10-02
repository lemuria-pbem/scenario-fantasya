<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Fantasya\SingletonCatalog as EngineSingletonCatalog;
use Lemuria\Engine\Orders;
use Lemuria\Engine\Fantasya\Storage\LemuriaConfig;
use Lemuria\Factory\DefaultBuilder;
use Lemuria\Model\Builder;
use Lemuria\Model\Fantasya\SingletonCatalog as ModelSingletonCatalog;
use Lemuria\Scenario\Fantasya\SingletonCatalog as ScenarioSingletonCatalog;

abstract class ScenarioConfig extends LemuriaConfig
{
	public function Builder(): Builder {
		$builder = new DefaultBuilder();
		$builder->register(new ModelSingletonCatalog())->register(new EngineSingletonCatalog())->register(new ScenarioSingletonCatalog());
		return $builder->profileRegistrationDone();
	}

	public function Orders(): Orders {
		return new ScenarioOrders();
	}
}
