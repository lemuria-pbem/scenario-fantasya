<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Command;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Act;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\Due;
use Lemuria\Scenario\Fantasya\Script\IdTrait;
use Lemuria\Storage\Ini\Section;

class SetOrders extends AbstractScene
{
	use IdTrait;

	/**
	 * @var array<Command>
	 */
	protected array $orders = [];

	/**
	 * @var array<Act>
	 */
	protected array $acts = [];

	/**
	 * @var array<Act>
	 */
	protected array $chain = [];

	/**
	 * @throws ParseException
	 */
	public function parse(Section $section): static {
		parent::parse($section);
		$this->context()->setUnit($this->parseUnit());
		foreach ($this->lines as $line) {
			$macro = Macro::parse($line);
			if ($macro) {
				$this->acts[] = $this->scenarioFactory->createAct($this, $macro);
			} else {
				$this->orders[] = $this->factory()->create(new Phrase($line));
			}
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$state = State::getInstance();
		foreach ($this->orders as $command) {
			$state->injectIntoTurn($command);
		}
		foreach ($this->acts as $act) {
			$act->play();
		}
		return $this;
	}

	public function prepareNext(): ?Section {
		$this->lines->clear();
		if ($this->id && $this->hasRound()) {
			foreach ($this->orders as $command) {
				$this->lines->add((string)$command->Phrase());
			}
		} elseif (!empty($this->orders)) {
			foreach (Lemuria::Orders()->getDefault($this->id) as $command) {
				$this->lines->add($command);
			}
		}

		foreach ($this->chain as $act) {
			if ($act->getChainResult()) {
				break;
			}
		}
		foreach ($this->acts as $act) {
			$act->prepareNext();
		}

		if (!$this->due || $this->due === Due::Future) {
			$this->replaceIdArgument();
		}
		return $this->section->Lines()->isEmpty() && $this->section->Values()->isEmpty() ? null : $this->section;
	}

	public function chain(Act $act): static {
		$this->chain[] = $act;
		return $this;
	}
}
