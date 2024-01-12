<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Engine\Event\CollectRumour;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Model\Myth;
use Lemuria\Scenario\Fantasya\Model\Rumour;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;

/**
 * Collect rumours to tell later.
 */
class Hearsay extends AbstractAct
{
	/**
	 * @var array<Rumour>
	 */
	private array $interest = [];

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$n = $macro->count();
		if ($n > 0) {
			for ($i = 1; $i <= $n; $i++) {
				$topic = $macro->getParameter($i);
				$myth  = Myth::tryFrom($topic);
				if ($myth) {
					$this->interest[] = $myth;
				} else {
					Lemuria::Log()->debug('Invalid hearsay myth given: ' . $topic);
				}
			}
		} else {
			foreach (Myth::cases() as $myth) {
				$this->interest[] = new Rumour($myth);
			}
		}
		return $this;
	}

	public function play(): static {
		CollectRumour::register($this);

		$myths = [];
		foreach ($this->interest as $rumour) {
			$myths[] = $rumour->Myth()->value;
		}
		$this->macro->setParameters($myths);

		return $this;
	}

	public function getChainResult(): bool {
		return true;
	}

	/**
	 * @return array<Rumour>
	 */
	public function Interest(): array {
		return $this->interest;
	}

	public function collect(): void {
		//TODO
	}
}
