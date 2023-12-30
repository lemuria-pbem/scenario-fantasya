<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Message\Unit\TempMessage;
use Lemuria\Exception\IdException;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Race;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Exception\DuplicateUnitException;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Storage\Ini\Section;

class CreateUnit extends AbstractScene
{
	use BuilderTrait;
	use MessageTrait;

	private ?Id $id;

	private ?string $name;

	private ?string $description;

	private Race $race;

	private int $size;

	private Region $region;

	private Knowledge $knowledge;

	private Resources $inventory;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->id          = $this->parseId($this->getOptionalValue('ID'));
		$this->name        = $this->getOptionalValue('Name');
		$this->description = $this->getOptionalValue('Beschreibung');
		$this->race        = $this->factory()->parseRace($this->getValue('Rasse'));
		$this->size        = (int)$this->getOptionalValue('Anzahl');
		$this->region      = Region::get(Id::fromId($this->getValue('Region')));
		$this->knowledge   = new Knowledge();
		$this->inventory   = new Resources();
		foreach ($this->getValues('Talent') as $talent) {
			$this->knowledge->add($this->parseAbility($talent));
		}
		foreach ($this->getValues('Besitz') as $item) {
			$this->inventory->add($this->parseQuantity($item));
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$unit = new Unit();
		$id   = $this->createId();
		$unit->setId($id);
		$party = $this->context()->Party();
		$party->People()->add($unit);
		$this->region->Residents()->add($unit);

		$unit->setName($this->name ?? 'Einheit ' . $id);
		if ($this->description) {
			$unit->setDescription($this->description);
		}
		$unit->setRace($this->race);
		$unit->setSize($this->size > 0 ? $this->size : 1);
		$unit->Knowledge()->fill($this->knowledge);
		$unit->Inventory()->fill($this->inventory);

		$presettings = $party->Presettings();
		$unit->setBattleRow($presettings->BattleRow());
		$unit->setIsLooting($presettings->IsLooting());
		$unit->setIsHiding($presettings->IsHiding());
		$unit->setDisguise($presettings->Disguise());

		if ($this->id) {
			$this->mapper()->map($unit, $this->id);
		}
		$this->context()->setUnit($unit);
		$this->message(TempMessage::class, $unit);

		return $this;
	}

	public function prepareNext(): ?Section {
		if ($this->hasRound()) {
			return $this->Section();
		}
		return null;
	}

	/**
	 * @throws ParseException
	 */
	protected function parseId(?string $id): ?Id {
		if ($id) {
			$lcId = strtolower($id);
			try {
				return Id::fromId($lcId);
			} catch (IdException $e) {
				throw new ParseException('Invalid ID: ' . $id, previous: $e);
			}
		}
		return null;
	}

	/**
	 * @throws ParseException
	 */
	protected function parseAbility(string $talentLevel): Ability {
		if (preg_match('/^([^ ]+) +([1-9][0-9]*)$/', $talentLevel, $matches) === 1) {
			$talent = $this->factory()->talent($matches[1]);
			$level  = (int)$matches[2];
			return new Ability($talent, Ability::getExperience($level));
		}
		throw new ParseException('Invalid ability: ' . $talentLevel);
	}

	/**
	 * @throws ParseException
	 */
	protected function parseQuantity(string $item): Quantity {
		if (preg_match('/^([1-9][0-9]*) +([^ ]+.*)$/', $item, $matches) === 1) {
			$count     = (int)$matches[1];
			$commodity = $this->factory()->commodity($matches[2]);
			return new Quantity($commodity, $count);
		}
		throw new ParseException('Invalid quantity: ' . $item);
	}

	/**
	 * @throws DuplicateUnitException
	 */
	protected function createId(): Id {
		if ($this->id) {
			if ($this->mapper()->has($this->id)) {
				throw new DuplicateUnitException($this->id);
			}
			if (!Lemuria::Catalog()->has($this->id, Domain::Unit)) {
				return $this->id;
			}
		}
		return Lemuria::Catalog()->nextId(Domain::Unit);
	}
}
