<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Lemuria;
use Lemuria\Scenario\Scripts;

class LemuriaScripts implements Scripts
{
	/**
	 * @var array<Script>
	 */
	private array $scripts = [];

	public function load(): static {
		foreach (Lemuria::Game()->getScripts() as $file => $data) {
			$script          = new Script($file, $data);
			$this->scripts[] = $script;
		}
		return $this;
	}

	public function play(): static {
		foreach ($this->scripts as $script) {
			$script->play();
		}
		return $this;
	}

	public function save(): static {
		$scripts = [];
		foreach ($this->scripts as $script) {
			$data = $script->prepareNext()->Data();
			if ($data->count() > 0) {
				$scripts[$script->File()] = $data;
			}
		}
		Lemuria::Game()->setScripts($scripts);
		return $this;
	}
}
