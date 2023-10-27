<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Id;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Exception\UnmappedAliasException;

final class UnitMapper
{
	/**
	 * @var array<int, Unit>
	 */
	private array $map = [];

	public function has(Id $alias): bool {
		return isset($this->map[$alias->Id()]);
	}

	/**
	 * @throws UnmappedAliasException
	 */
	public function getUnit(Id $alias): Unit {
		$id = $alias->Id();
		if (isset($this->map[$id])) {
			return $this->map[$id];
		}
		throw new UnmappedAliasException($alias);
	}

	public function map(Unit $unit, Id $alias): void {
		$this->map[$alias->Id()] = $unit;
	}
}
