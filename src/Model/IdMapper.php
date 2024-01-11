<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Entity;
use Lemuria\Id;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Scenario\Fantasya\Exception\UnmappedAliasException;

final class IdMapper
{
	/**
	 * @var array<int, array<int, Unit>>
	 */
	private array $map = [];

	public function has(Domain $domain, Id $alias): bool {
		return isset($this->map[$domain->value][$alias->Id()]);
	}

	public function forAlias(Domain $domain, Id $alias): Id {
		$entity = $this->map[$domain->value][$alias->Id()] ?? null;
		return $entity ? $entity->Id() : $alias;
	}

	/**
	 * @throws UnmappedAliasException
	 */
	public function getUnit(Id $alias): Unit {
		/** @var Unit $unit */
		$unit = $this->getEntity(Domain::Unit, $alias);
		return $unit;
	}

	/**
	 * @throws UnmappedAliasException
	 */
	public function getConstruction(Id $alias): Construction {
		/** @var Construction $construction */
		$construction = $this->getEntity(Domain::Construction, $alias);
		return $construction;
	}

	public function getVessel(Id $alias): Vessel {
		/** @var Vessel $vessel */
		$vessel = $this->getEntity(Domain::Vessel, $alias);
		return $vessel;
	}

	public function map(Entity $unit, Id $alias): void {
		$this->map[$unit->Catalog()->value][$alias->Id()] = $unit;
	}

	/**
	 * @throws UnmappedAliasException
	 */
	private function getEntity(Domain $domain, Id $alias): Entity {
		$id = $alias->Id();
		if (isset($this->map[$domain->value][$id])) {
			return $this->map[$domain->value][$id];
		}
		throw new UnmappedAliasException($alias);
	}
}
