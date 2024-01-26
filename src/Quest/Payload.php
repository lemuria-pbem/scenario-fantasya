<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest;

use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Scenario\Payload as PayloadModel;
use Lemuria\Scenario\Fantasya\Exception\ReservedOffsetException;
use Lemuria\SerializableTrait;
use Lemuria\Validate;

class Payload implements \ArrayAccess, PayloadModel
{
	use SerializableTrait;

	private const string STATUS = 'status';

	private array $data = [
		self::STATUS => []
	];

	public function offsetExists(mixed $offset): bool {
		return $this->checkOffset($offset, false) && array_key_exists($offset, $this->data);
	}

	public function offsetGet(mixed $offset): mixed {
		return $this->checkOffset($offset) ? ($this->data[$offset] ?? null) : null;
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		if ($this->checkOffset($offset)) {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset(mixed $offset): void {
		if ($this->checkOffset($offset)) {
			unset($this->data[$offset]);
		}
	}

	public function serialize(): array {
		$statuses = [];
		foreach ($this->data[self::STATUS] as $id => $status) {
			/** @var Status $status */
			$statuses[$id] = $status->value;
		}
		return [self::STATUS => $statuses];
	}

	public function unserialize(array $data): static {
		$statuses = [];
		foreach ($data[self::STATUS] as $id => $value) {
			$statuses[$id] = Status::from($value);
		}
		$this->data[self::STATUS] = $statuses;
		return $this;
	}

	public function status(Party $party): Status {
		$id = $party->Id()->Id();
		return $this->data[self::STATUS][$id] ?? Status::None;
	}

	public function setStatus(Party $party, Status $status): void {
		$id = $party->Id()->Id();
		$this->data[self::STATUS][$id] = $status;
	}

	protected function validateSerializedData(array $data): void {
		$this->validate($data, self::STATUS, Validate::Array);
	}

	private function checkOffset(mixed $offset, bool $throwException = true): bool {
		if ($offset === self::STATUS) {
			if ($throwException) {
				throw new ReservedOffsetException($offset);
			}
			return false;
		}
		return true;
	}
}
