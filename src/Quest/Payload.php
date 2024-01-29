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

	public final const int IMMORTAL = PHP_INT_MAX;

	private const string STATUS = 'status';

	private const string TTL = 'ttl';

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
		$data = [];
		foreach ($this->data as $key => $value) {
			if ($key === self::STATUS) {
				$statuses = [];
				foreach ($value as $id => $status) {
					/** @var Status $status */
					$statuses[$id] = $status->value;
				}
				$data[self::STATUS] = $statuses;
			} else {
				$data[$key] = $value;
			}
		}
		return $data;
	}

	public function unserialize(array $data): static {
		$this->data = [];
		foreach ($data as $key => $value) {
			if ($key === self::STATUS) {
				$statuses = [];
				foreach ($value as $id => $status) {
					$statuses[$id] = Status::from($status);
				}
				$this->data[self::STATUS] = $statuses;
			} else {
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	public function hasAnyStatus(Status $any): bool {
		foreach ($this->data[self::STATUS] as $status) {
			if ($status === $any) {
				return true;
			}
		}
		return false;
	}

	public function status(Party $party): Status {
		$id = $party->Id()->Id();
		return $this->data[self::STATUS][$id] ?? Status::None;
	}

	public function setStatus(Party $party, Status $status): static {
		$id                            = $party->Id()->Id();
		$this->data[self::STATUS][$id] = $status;
		return $this;
	}

	public function ttl(): int {
		return $this->data[self::TTL] ?? PHP_INT_MAX;
	}

	public function setTtl(int $ttl): static {
		if ($ttl >= 0) {
			$this->data[self::TTL] = $ttl;
		} else {
			unset($this->data[self::TTL]);
		}
		return $this;
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
