<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest\Controller;

use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\Unicum;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;

class SellUnicum extends AbstractController
{
	use BuilderTrait;

	protected const int TTL = 1;

	private const string PARTY = 'party';

	private const string UNICUM = 'unicum';

	private const string PAYMENT = 'payment';

	public function Unicum(): Unicum {
		return Unicum::get(new Id((int)$this->getFromPayload(self::UNICUM)));
	}

	public function Payment(): Quantity {
		$payment = $this->payload()->offsetGet(self::PAYMENT);
		return new Quantity(self::createCommodity(key($payment)), current($payment));
	}

	public function isAvailableFor(Party|Unit $subject): bool {
		$party = $subject instanceof Party ? $subject : $subject->Party();
		return $party->Id()->Id() === $this->getFromPayload(self::PARTY);
	}

	public function setSeller(Party|Unit $seller): static {
		$party = $seller instanceof Party ? $seller : $seller->Party();
		$this->initTtl()->offsetSet(self::PARTY, $party->Id()->Id());
		return $this;
	}

	public function setUnicum(Unicum $unicum): static {
		$this->initTtl()->offsetSet(self::UNICUM, $unicum->Id()->Id());
		return $this;
	}

	public function setPayment(Quantity $payment): static {
		$this->initTtl()->offsetSet(self::PAYMENT, [(string)$payment->Commodity() => $payment->Count()]);
		return $this;
	}

	protected function updateStatus(): void {
		$inventory = $this->canBeFulfilled();
		if ($inventory) {
			$merchant = $this->quest()->Unit();
			$unicum   = $this->Unicum();
			$payment  = $this->Payment();
			$inventory->remove($payment);
			$this->unit->Treasury()->remove($unicum);
			$merchant->Treasury()->add($unicum);
			$this->unit->Inventory()->add(new Quantity($payment->Commodity(), $payment->Count()));
			$this->setStatus(Status::Completed);
			Lemuria::Log()->debug('Unicum ' . $unicum->Id() . ' sold from ' . $this->unit . ' to ' . $merchant . ' for ' . $payment . '.');
		}
	}

	protected function checkForFinish(): bool {
		return (bool)$this->canBeFulfilled();
	}

	protected function initTtl(): Payload {
		return $this->payload()->setTtl(self::TTL);
	}

	private function canBeFulfilled(): ?Resources {
		if ($this->unit->Treasury()->has($this->Unicum()->Id())) {
			$inventory = $this->quest()->Unit()->Inventory();
			$payment   = $this->Payment();
			$available = $inventory->offsetGet($payment->Commodity());
			return $available->Count() >= $payment->Count() ? $inventory : null;
		}
		return null;
	}
}
