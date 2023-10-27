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
				self::$factory->create($section)->play();
			} catch (ParseException|UnknownSceneException $e) {
				Lemuria::Log()->critical($e->getMessage());
			} catch (ScriptException $e) {
				Lemuria::Log()->error($e->getMessage());
			}
		}
		return $this;
	}
}
