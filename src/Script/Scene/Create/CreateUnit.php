<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene\Create;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Message\Unit\TempMessage;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Aura;
use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Race;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\SpellBook;
use Lemuria\Model\Fantasya\Talent\Magic;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Storage\Ini\Section;

class CreateUnit extends AbstractCreate
{
	use BuilderTrait;
	use MessageTrait;

	private Race $race;

	private int $size;

	private ?Region $region = null;

	private ?Construction $construction = null;

	private ?Vessel $vessel = null;

	private Knowledge $knowledge;

	private Resources $inventory;

	private SpellBook $spellBook;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->id          = $this->parseId($this->getOptionalValue('ID'));
		$this->name        = $this->getOptionalValue('Name');
		$this->description = $this->getOptionalValue('Beschreibung');
		$this->race        = $this->factory()->parseRace($this->getValue('Rasse'));
		$size              = $this->getOptionalValue('Größe');
		$this->size        = is_string($size) ? (int)$size : 1;
		$this->knowledge   = new Knowledge();
		$this->inventory   = new Resources();
		$this->spellBook   = new SpellBook();
		foreach ($this->getValues('Talent') as $talent) {
			$this->knowledge->add($this->parseAbility($talent));
		}
		foreach ($this->getValues('Besitz') as $item) {
			$this->inventory->add($this->parseQuantity($item));
		}
		foreach ($this->getValues('Zauber') as $spell) {
			$this->spellBook->add($this->factory()->spell($spell));
		}
		$region = $this->getOptionalValue('Region');
		if ($region) {
			$this->region = Region::get(Id::fromId($region));
		}
		$construction = $this->getOptionalValue('Gebäude');
		if ($construction) {
			$this->construction = Construction::get($this->mapper()->forAlias(Domain::Construction, Id::fromId($construction)));
			if (!$this->region) {
				$this->region = $this->construction->Region();
			}
		}
		$vessel = $this->getOptionalValue('Schiff');
		if ($vessel) {
			$this->vessel = Vessel::get($this->mapper()->forAlias(Domain::Vessel, Id::fromId($vessel)));
			if (!$this->region) {
				$this->region = $this->vessel->Region();
			}
		}
		if (!$region && !$construction && !$vessel) {
			throw new ParseException('One of region|construction|vessel must be given.');
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$unit = new Unit();
		$id   = $this->createId(Domain::Unit);
		$unit->setId($id);
		$party = $this->context()->Party();
		$party->People()->add($unit);
		$this->region->Residents()->add($unit);
		$this->construction?->Inhabitants()->add($unit);
		$this->vessel?->Passengers()->add($unit);

		$unit->setName($this->name ?? 'Einheit ' . $id);
		if ($this->description) {
			$unit->setDescription($this->description);
		}
		$unit->setRace($this->race);
		$unit->setSize($this->size);
		$unit->Knowledge()->fill($this->knowledge);
		$unit->Inventory()->fill($this->inventory);

		$presettings = $party->Presettings();
		$unit->setBattleRow($presettings->BattleRow());
		$unit->setIsLooting($presettings->IsLooting());
		$unit->setIsHiding($presettings->IsHiding());
		$unit->setDisguise($presettings->Disguise());

		$this->initMagic($unit);

		if ($this->id) {
			$this->mapper()->map($unit, $this->id);
		}
		$this->context()->setUnit($unit);
		$this->message(TempMessage::class, $unit);
		Lemuria::Log()->debug('New unit ' . $unit . ' created.');

		$resources = $this->region->Resources();
		$peasant   = self::createCommodity(Peasant::class);
		$recruits  = min($this->size, $resources[$peasant]->Count());
		if ($recruits > 0) {
			$quantity = new Quantity($peasant, $recruits);
			$resources->remove($quantity);
			Lemuria::Log()->debug($quantity . ' removed from resources in ' . $this->region . '.');
		}

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

	private function initMagic(Unit $unit): void {
		if ($this->knowledge->offsetExists(Magic::class)) {
			$magic  = $this->knowledge->offsetGet(Magic::class);
			$points = $magic->Level() ** 2;
			$aura   = new Aura();
			$aura->setMaximum($points)->setAura($points);
			$unit->setAura($aura);
			foreach ($this->spellBook as $spell) {
				$unit->Party()->SpellBook()->add($spell);
			}
		}
	}
}
