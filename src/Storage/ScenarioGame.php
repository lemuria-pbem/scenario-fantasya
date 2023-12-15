<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Storage;

use Lemuria\Engine\Fantasya\Storage\LemuriaGame;
use Lemuria\Storage\Ini\SectionList;
use Lemuria\Storage\IniProvider;

class ScenarioGame extends LemuriaGame
{
	private const string SCRIPTS_DIR = 'scripts';

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
}
