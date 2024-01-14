<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Storage;

use Lemuria\Engine\Fantasya\Storage\LemuriaGame;
use Lemuria\Model\Fantasya\Storage\JsonProvider;
use Lemuria\Storage\Ini\SectionList;
use Lemuria\Storage\IniProvider;

class ScenarioGame extends LemuriaGame
{
	private const string SCRIPTS_DIR = 'scripts';

	private const string STRINGS_DIR = __DIR__ . '/../../resources';

	private const string STRINGS_FILE = 'scenario.json';

	public function getStrings(): array {
		$strings  = parent::getStrings();
		$scenario = $this->getData(self::STRINGS_FILE);
		return array_merge($strings, $scenario);
	}

	/**
	 * Get NPC scripts data.
	 *
	 * @return array<string, SectionList>
	 */
	public function getScripts(): array {
		$data       = [];
		$scriptsDir = $this->config->getStoragePath() . DIRECTORY_SEPARATOR . self::SCRIPTS_DIR;
		$pathPos    = strlen($scriptsDir) + 1;
		$provider   = new IniProvider($scriptsDir);
		foreach ($provider->glob() as $path) {
			$file        = substr($path, $pathPos);
			$data[$file] = $provider->read($file);
		}
		return $data;
	}

	/**
	 * Set NPC scripts data.
	 *
	 * @var array<string, SectionList> $scripts
	 */
	public function setScripts(array $scripts): static {
		$scriptsDir = $this->config->getStoragePath() . DIRECTORY_SEPARATOR . self::SCRIPTS_DIR;
		$provider   = new IniProvider($scriptsDir);
		foreach ($scripts as $file => $data) {
			$provider->write($file, $data);
		}
		return $this;
	}

	/**
	 * @return array<string, string>
	 */
	protected function addStringsStorage(array $storage): array {
		$storage = parent::addStringsStorage($storage);
		$storage[self::STRINGS_FILE] = new JsonProvider(self::STRINGS_DIR);
		return $storage;
	}
}
