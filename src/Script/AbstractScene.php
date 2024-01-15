<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Factory\CommandFactory;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Factory;
use Lemuria\Scenario\Fantasya\Model\IdMapper;
use Lemuria\Scenario\Fantasya\Scene;
use Lemuria\Scenario\Fantasya\Script;
use Lemuria\Storage\Ini\Lines;
use Lemuria\Storage\Ini\Section;
use Lemuria\Storage\Ini\Values;

abstract class AbstractScene implements Scene
{
	private const string ROUND = 'Runde';

	protected Section $section;

	protected Values $values;

	protected Lines $lines;

	protected readonly ?Due $due;

	private static ?Context $context = null;

	private static CommandFactory $factory;

	private static IdMapper $mapper;

	private bool $dueInitialized = false;

	public function __construct(protected readonly Factory $scenarioFactory, protected readonly Script $script) {
		if (!self::$context) {
			self::$context = new Context(State::getInstance());
			self::$context->setParty(State::getInstance()->getTurnOptions()->Finder()->Party()->findByType(Type::NPC));
			self::$factory = self::$context->Factory();
			self::$mapper  = new IdMapper();
		}
	}

	public function Script(): Script {
		return $this->script;
	}

	public function Section(): Section {
		return $this->section;
	}

	public function isDue(): bool {
		if (!$this->dueInitialized) {
			$round                = $this->getOptionalValue(self::ROUND);
			$this->due            = $round ? Due::forRound((int)$round) : null;
			$this->dueInitialized = true;
		}
		return !$this->due || $this->due === Due::Now;
	}

	public function parse(Section $section): static {
		$this->section = $section;
		$this->values  = $section->Values();
		$this->lines   = $section->Lines();
		return $this;
	}

	public function play(): static {
		Lemuria::Log()->debug('Playing scene ' . $this->section->Name() . '.');
		return $this;
	}

	public function hasRound(): bool {
		return (bool)$this->getOptionalValue(self::ROUND);
	}

	public function setArguments(string $arguments): static {
		return $this;
	}

	public function context(): Context {
		return self::$context;
	}

	protected function factory(): CommandFactory {
		return self::$factory;
	}

	protected function mapper(): IdMapper {
		return self::$mapper;
	}

	/**
	 * @throws ParseException
	 */
	protected function getValue(string $name): string {
		if ($this->values->offsetExists($name)) {
			return (string)$this->values->offsetGet($name);
		}
		throw new ParseException('Undefined value: ' . $name);
	}

	protected function getOptionalValue(string $name): ?string {
		if ($this->values->offsetExists($name)) {
			return (string)$this->values->offsetGet($name);
		}
		return null;
	}

	/**
	 * @return array<string>
	 */
	protected function getValues(string $name): array {
		if ($this->values->offsetExists($name)) {
			$values = [];
			foreach ($this->values->offsetGet($name) as $value) {
				foreach (explode(',', $value) as $part) {
					$part = trim($part);
					if ($part) {
						$values[] = $part;
					}
				}
			}
			return $values;
		}
		return [];
	}
}
