<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Command;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Id;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Act;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Storage\Ini\Section;

class SetOrders extends AbstractScene
{
	/**
	 * @var array<Command>
	 */
	protected array $orders = [];

	/**
	 * @var array<Act>
	 */
	protected array $acts = [];

	protected ?Id $id = null;

	/**
	 * @var array<Act>
	 */
	protected array $chain = [];

	public function setArguments(string $arguments): static {
		if ($arguments) {
			$this->id = Id::fromId($arguments);
		}
		return $this;
	}

	/**
	 * @throws ParseException
	 */
	public function parse(Section $section): static {
		parent::parse($section);
		if (isset($this->values['ID'])) {
			$this->id = Id::fromId((string)$this->values['ID']);
		}
		if (!$this->id) {
			throw new ParseException('No unit defined in this script.');
		}

		$mapper = $this->mapper();
		$unit   = $mapper->has($this->id) ? $mapper->getUnit($this->id) : Unit::get($this->id);
		if ($unit->Party()->Type() !== Type::NPC) {
			throw new ParseException($unit . ' is no NPC unit.');
		}

		$this->context()->setUnit($unit);
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
		foreach ($this->orders as $command) {
			$this->lines->add((string)$command->Phrase());
		}
		foreach ($this->chain as $act) {
			if ($act->getChainResult()) {
				break;
			}
		}
		foreach ($this->acts as $act) {
			$act->prepareNext();
		}
		return $this->section;
	}

	public function chain(Act $act): static {
		$this->chain[] = $act;
		return $this;
	}
}
