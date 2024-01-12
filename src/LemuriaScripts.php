<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Engine\Fantasya\Turn\Options;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Scenario\Scripts;

class LemuriaScripts implements Scripts
{
	private readonly Context $context;

	/**
	 * @var array<Script>
	 */
	private array $scripts = [];

	public function __construct(private readonly Options $options) {
		$this->context = new Context(State::getInstance());
	}

	public function load(): static {
		$this->context->setParty($this->options->Finder()->Party()->findByType(Type::NPC));
		Script::setContext($this->context);

		Lemuria::Log()->debug('Loading NPC scripts.');
		foreach (Lemuria::Game()->getScripts() as $file => $data) {
			$script          = new Script($file, $data);
			$this->scripts[] = $script;
		}
		return $this;
	}

	public function play(): static {
		Lemuria::Log()->debug('Playing NPC scripts.');
		foreach ($this->scripts as $script) {
			$script->play();
		}
		return $this;
	}

	public function save(): static {
		Lemuria::Log()->debug('Saving MPC scripts.');
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
