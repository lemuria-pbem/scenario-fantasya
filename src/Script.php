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
	protected static ?Factory $factory = null;

	/**
	 * @var array<Scene>
	 */
	protected array $scenes = [];

	public function __construct(private readonly string $file, private SectionList $data) {
		if (!self::$factory) {
			self::$factory = new Factory();
		}
	}

	public function File(): string {
		return $this->file;
	}

	public function Data(): SectionList {
		return $this->data;
	}

	public function play(): static {
		Lemuria::Log()->debug('Playing NPC script ' . basename($this->file) . '.');
		foreach ($this->data->getSections() as $section) {
			try {
				$scene = self::$factory->createScene($section);
				if ($scene->isDue()) {
					$scene->play();
					$this->scenes[] = $scene;
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

	public function prepareNext(): static {
		$this->data->clear();
		foreach ($this->scenes as $scene) {
			$section = $scene->prepareNext();
			if ($section) {
				$this->data->add($section);
			}
		}
		return $this;
	}
}
