<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Fantasya\LemuriaOrders;
use Lemuria\Engine\Instructions;
use Lemuria\Id;
use Lemuria\StringList;
use Lemuria\Validate;

class ScenarioOrders extends LemuriaOrders
{
	private const string SCENARIO = 'scenario';

	private const string ID = 'id';

	private const string ACTS = 'acts';

	/**
	 * @var array<int, array>
	 */
	private array $scenario = [];

	/**
	 * Get the list of current orders for an entity.
	 */
	public function getScenario(Id $id): Instructions {
		$id = $id->Id();
		if (!isset($this->scenario[$id])) {
			$this->scenario[$id] = new StringList();
		}
		return $this->scenario[$id];
	}

	public function clear(): static {
		parent::clear();
		$this->scenario = [];
		return $this;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	protected function validateSerializedData(array $data): void {
		parent::validateSerializedData($data);
		$this->validateIfExists($data, self::SCENARIO, Validate::Array);
	}

	protected function loadData(array $orders): static {
		parent::loadData($orders);
		foreach ($orders[self::SCENARIO] ?? [] as $data) {
			$this->validate($data, self::ID, Validate::Int);
			$this->validate($data, self::ACTS, Validate::Array);
			$this->getScenario(new Id($data[self::ID]))->unserialize($data[self::ACTS]);
		}
		return $this;
	}

	protected function saveData(array &$data): static {
		$scenario = [];
		ksort($this->scenario);
		foreach ($this->scenario as $id => $instructions /** @var Instructions $instructions */) {
			$scenario[] = [self::ID => $id, self::ACTS => $instructions->serialize()];
		}
		$this->data = $scenario;

		$data[self::SCENARIO] = $scenario;
		return parent::saveData($data);
	}
}
