<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Command;
use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Factory\RumorTrait;
use Lemuria\Id;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\IdTrait;
use Lemuria\Storage\Ini\Section;
use Lemuria\Storage\Ini\Value;

class SpreadRumour extends AbstractScene
{
	use IdTrait;
	use MessageTrait;
	use RumorTrait;

	private const string ROUNDS = 'Runden';

	private const string KEY = 'Schlüssel';

	private const string PARTY = 'Partei';

	private const string REGION = 'Region';

	/**
	 * @var array<Command>
	 */
	protected array $orders = [];

	protected ?int $rounds = null;

	protected string $key = '';

	protected ?Party $origin = null;

	protected ?Region $locality = null;

	public function Id(): Id {
		return $this->id;
	}

	public function Key(): string {
		return $this->key;
	}

	public function Origin(): ?Party {
		return $this->origin;
	}

	public function Locality(): ?Region {
		return $this->locality;
	}

	/**
	 * @throws ParseException
	 */
	public function parse(Section $section): static {
		parent::parse($section);
		$this->context()->setUnit($this->parseUnit());
		if ($this->values->offsetExists(self::ROUNDS)) {
			$this->rounds = (int)(string)$this->values[self::ROUNDS];
		}
		if ($this->values->offsetExists(self::KEY)) {
			$this->key = (string)$this->values[self::KEY];
		}
		if ($this->values->offsetExists(self::PARTY)) {
			$this->origin = Party::get(Id::fromId((string)$this->values[self::PARTY]));
		}
		if ($this->values->offsetExists(self::REGION)) {
			$this->locality = Region::get(Id::fromId((string)$this->values[self::REGION]));
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		foreach ($this->lines as $rumor) {
			$this->createRumor($this->context()->Unit(), $rumor, $this->origin, $this->locality);
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
		if ($this->key) {
			$this->values[self::KEY] = new Value($this->key);
		}
		if ($this->origin) {
			$this->values[self::PARTY] = new Value((string)$this->origin->Id());
		}
		if ($this->locality) {
			$this->values[self::REGION] = new Value((string)$this->locality->Id());
		}
		return null;
	}

	public function replace(): static {
		$this->rounds = 0;
		return $this;
	}
}
