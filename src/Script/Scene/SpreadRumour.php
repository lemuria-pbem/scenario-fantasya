<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Command;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\IdTrait;
use Lemuria\Storage\Ini\Section;
use Lemuria\Storage\Ini\Value;

class SpreadRumour extends AbstractScene
{
	use IdTrait;

	private const string ROUNDS = 'Runden';

	/**
	 * @var array<Command>
	 */
	protected array $orders = [];

	protected ?int $rounds = null;

	/**
	 * @throws ParseException
	 */
	public function parse(Section $section): static {
		parent::parse($section);
		$this->context()->setUnit($this->parseUnit());
		foreach ($this->lines as $line) {
			$this->orders[] = $this->factory()->create(new Phrase('GERÜCHT ' . $line));
		}
		if ($this->values->offsetExists(self::ROUNDS)) {
			$this->rounds = (int)(string)$this->values[self::ROUNDS];
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$state = State::getInstance();
		foreach ($this->orders as $command) {
			$state->injectIntoTurn($command);
		}
		if ($this->rounds > 0) {
			$this->rounds--;
		}
		return $this;
	}

	public function prepareNext(): ?Section {
		if (!$this->isDue()) {
			return $this->section;
		}

		if ($this->rounds > 0) {
			$this->values[self::ROUNDS] = new Value((string)$this->rounds);
		}
		if ($this->rounds === null || $this->rounds > 0) {
			$this->replaceIdArgument();
			return $this->section;
		}
		return null;
	}
}
