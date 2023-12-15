<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\UnknownActException;
use Lemuria\Scenario\Fantasya\Exception\UnknownSceneException;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\Act\Market;
use Lemuria\Scenario\Fantasya\Script\Act\Roundtrip;
use Lemuria\Scenario\Fantasya\Script\Act\Trip;
use Lemuria\Scenario\Fantasya\Script\Scene\CreateUnit;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Storage\Ini\Section;

class Factory
{
	/**
	 * @type array<string, string>
	 */
	protected const array SCENE = [
		'Einheit' => CreateUnit::class,
		'Skript'  => SetOrders::class
	];

	/**
	 * @type array<string, string>
	 */
	protected const array ACT = [
		'Marktstand' => Market::class,
		'Reise'      => Trip::class,
		'Rundreise'  => Roundtrip::class
	];

	/**
	 * @throws ParseException
	 * @throws UnknownSceneException
	 */
	public function createScene(Section $section): Scene {
		$name  = $section->Name();
		$space = strpos($name, ' ');
		if ($space > 1) {
			$arguments = trim(substr($name, $space));
			$name      = substr($name, 0, $space);
		} else {
			$arguments = '';
		}

		$class = self::SCENE[$name] ?? null;
		if (!$class) {
			throw new UnknownSceneException($name);
		}
		$scene = new $class($this);
		if ($arguments) {
			$scene->setArguments($arguments);
		}
		return $scene->parse($section);
	}

	public function createAct(AbstractScene $scene, Macro $macro): Act {
		$name = $macro->getAct();
		$class = self::ACT[$name] ?? null;
		if (!$class) {
			throw new UnknownActException($name);
		}
		/** @var Act $act */
		$act = new $class($scene);
		return $act->parse($macro);
	}
}
