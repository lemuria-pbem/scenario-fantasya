<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Factory;

use Lemuria\Exception\SingletonException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Scenario\Quest\Controller;

trait BuilderTrait
{
	/**
	 * Create a quest controller singleton.
	 *
	 * @throws SingletonException
	 */
	protected static function createController(string $class): Controller {
		$controller = Lemuria::Builder()->create($class);
		if ($controller instanceof Controller) {
			return $controller;
		}
		throw new SingletonException($class, 'controller');
	}
}
