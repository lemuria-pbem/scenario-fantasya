<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\ScriptException;
use Lemuria\Scenario\Fantasya\Exception\UnknownSceneException;
use Lemuria\Storage\Ini\SectionList;

class Script
{
	protected static ?SceneFactory $factory = null;

	public function __construct(private readonly string $file, private SectionList $data) {
		if (!self::$factory) {
			self::$factory = new SceneFactory();
		}
	}

	public function File(): string {
		return $this->file;
	}

	public function Data(): SectionList {
		return $this->data;
	}

	public function play(): static {
		foreach ($this->data->getSections() as $section) {
			try {
				$scene = self::$factory->create($section);
				if ($scene->isDue()) {
					$scene->play();
				} else {
					Lemuria::Log()->debug('Scene of section ' . $section->Name() . ' is not scheduled to play this round.');
				}
			} catch (ParseException|UnknownSceneException $e) {
				Lemuria::Log()->critical($e->getMessage());
			} catch (ScriptException $e) {
				Lemuria::Log()->error($e->getMessage());
			}
		}
		return $this;
	}
}
