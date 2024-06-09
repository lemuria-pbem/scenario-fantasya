<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Exception\LemuriaException;
use Lemuria\Id;
use Lemuria\Model\Fantasya\Unit;

class CategoryDirectory implements \ArrayAccess
{
	/**
	 * @var array<int, Category>
	 */
	protected array $category = [];

	public function offsetExists($offset): true {
		return true;
	}

	/**
	 * @param Id|Unit|int|string $offset
	 */
	public function offsetGet(mixed $offset): Category {
		return $this->category[$this->index($offset)] ?? Category::NPC;
	}

	/**
	 * @param Id|Unit|int|string $offset
	 * @param Category $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($value instanceof Category) {
			if ($value === Category::NPC) {
				$this->offsetUnset($offset);
			} else {
				$this->category[$this->index($offset)] = $value;
			}
		} else {
			throw new LemuriaException('Invalid category given.');
		}
	}

	public function offsetUnset($offset): void {
		unset($this->category[$this->index($offset)]);
	}

	protected function index($offset): int {
		if ($offset instanceof Unit) {
			return $offset->Id()->Id();
		}
		if ($offset instanceof Id) {
			return $offset->Id();
		}
		if (is_string($offset)) {
			return Id::fromId($offset)->Id();
		}
		if (is_int($offset) && $offset > 0) {
			return $offset;
		}
		throw new LemuriaException('Invalid offset given.');
	}
}