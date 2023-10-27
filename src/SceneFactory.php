<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\ScriptException;
use Lemuria\Scenario\Fantasya\Exception\UnknownSceneException;
use Lemuria\Scenario\Fantasya\Script\CreateUnit;
use Lemuria\Scenario\Fantasya\Script\SetOrders;
use Lemuria\Storage\Ini\Section;

class SceneFactory
{
	protected const SCENE = ['Einheit' => CreateUnit::class, 'Skript' => SetOrders::class];

	/**
	 * @throws ParseException
	 * @throws UnknownSceneException
	 */
	public function create(Section $section): Scene {
		$name  = $section->Name();
		$class = self::SCENE[$name] ?? null;
		if (!$class) {
			throw new UnknownSceneException($name);
		}
		/** @var Scene $scene */
		$scene = new $class();
		return $scene->parse($section);
	}
}
