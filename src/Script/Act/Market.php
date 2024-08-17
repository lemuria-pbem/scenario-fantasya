<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Command\Alternative;
use Lemuria\Engine\Fantasya\Command\Trespass\Enter;
use Lemuria\Engine\Fantasya\Command\Vacate\Leave;
use Lemuria\Engine\Fantasya\Factory\ContextTrait;
use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Factory\SiegeTrait;
use Lemuria\Engine\Fantasya\Message\Construction\EnterNoSpaceMessage;
use Lemuria\Engine\Fantasya\Message\Construction\EnterNotAllowedMessage;
use Lemuria\Engine\Fantasya\Message\Unit\EnterDeniedMessage;
use Lemuria\Engine\Fantasya\Message\Unit\EnterMessage;
use Lemuria\Engine\Fantasya\Message\Unit\EnterSiegeMessage;
use Lemuria\Engine\Fantasya\Message\Unit\EnterTooLargeMessage;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Market as MarketBuilding;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Estate;
use Lemuria\Model\Fantasya\Extension\Trades;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Relation;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Engine\Event\EnterMarkets;
use Lemuria\Scenario\Fantasya\Engine\Event\MarketTrade;
use Lemuria\Scenario\Fantasya\Engine\Event\TravelCommands;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\Storage\Ini\Values;

/**
 * Act: Marktstand(N)
 */
class Market extends AbstractAct
{
	use ContextTrait;
	use MessageTrait;
	use SiegeTrait;
	use VisitationTrait;

	private const string LAST_TRADE = 'LetzterHandel';

	private Values $values;

	private int $maxRounds;

	private int $lastTrade;

	public function __construct(SetOrders $scene) {
		parent::__construct($scene);
		$this->context = $scene->context();
		$this->values  = $scene->Section()->Values();
	}

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->maxRounds = (int)$macro->getParameter();
		$lastTrade       = $this->values[self::LAST_TRADE] ?? null;
		$this->lastTrade = (int)($lastTrade?->__toString());
		return $this;
	}

	public function play(): static {
		//TODO: Remove unused code parts.
		parent::play();
		$context = $this->scene->context();
		$unit    = $context->Unit();
		if ($this->isInMarket()) {
			MarketTrade::register($this);
			if (!$this->getChainResult()) {
				$leave = new Leave(new Phrase('VERLASSEN'), $context);
				State::getInstance()->injectIntoTurn($leave);
				EnterMarkets::register($this);
			} else {
				$this->addVisitationEffect();
				TravelCommands::cancelTravelFor($unit);
				EnterMarkets::register($this);
			}
		} else {
			$region  = $unit->Region();
			$markets = $this->findMarkets($unit, $region, true);
			if ($markets->isEmpty()) {
				EnterMarkets::register($this);
			} else {
				$state  = State::getInstance();
				$market = $markets->random();
				$enter  = new Enter(new Phrase('BETRETEN ' . $market->Id()), $context);
				$state->injectIntoTurn($enter);
				Lemuria::Log()->debug('Market act: ' . $unit . ' will enter market ' . $market . '.');
				if ($markets->count() > 1) {
					// TODO: Enter logic could be improved to consider all markets.
					Lemuria::Log()->debug('Market act in ' . $region . ' chose a random market, there are more.');
				}
				$learn = new Alternative(new Phrase('ALTERNATIVE LERNEN Handeln'), $context);
				$state->injectIntoTurn($learn);
				$this->addVisitationEffect();
				TravelCommands::cancelTravelFor($unit);
				$this->lastTrade = 0;
			}
		}

		return $this->addToChain();
	}

	public function getChainResult(): bool {
		if ($this->isInMarket()) {
			return $this->maxRounds <= 0 || $this->lastTrade <= $this->maxRounds;
		}
		return false;
	}

	public function prepareNext(): static {
		unset($this->values[self::LAST_TRADE]);
		$this->values[self::LAST_TRADE] = (string)$this->lastTrade;
		return parent::prepareNext();
	}

	public function setTrades(Trades $trades): void {
		if ($trades->count() > 0) {
			$this->lastTrade = 0;
		} else {
			$this->lastTrade++;
		}
	}

	public function enterAfterTrip(): void {
		$unit    = $this->unit;
		$region  = $this->unit->Region();
		$markets = $this->findMarkets($unit, $region);
		if ($markets->isEmpty()) {
			Lemuria::Log()->debug('There are no markets in ' . $region . ' - ' . $unit . ' does not enter.');
		} else {
			/** @var Construction $market */
			$market = $markets->random();
			$market->Inhabitants()->add($unit);
			$this->lastTrade = 0;
			$this->message(EnterMessage::class, $unit)->e($market);
			Lemuria::Log()->debug($unit . ' enters market ' . $market . '.');
			if ($markets->count() > 1) {
				// TODO: Enter logic could be improved to consider all markets.
				Lemuria::Log()->debug('Market act in ' . $region . ' chose a random market, there are more.');
			}
		}
	}

	protected function isInMarket(): bool {
		return $this->unit->Construction()?->Building() instanceof MarketBuilding;
	}

	protected function findMarkets(Unit $unit, Region $region, bool $withMessage = false): Estate {
		$markets = new Estate();
		foreach ($region->Estate() as $construction) {
			$building = $construction->Building();
			if ($building instanceof MarketBuilding) {
				$this->initSiege($construction);
				if ($construction->getFreeSpace() >= $unit->Size()) {
					if ($this->canEnterOrLeave($unit)) {
						$inhabitants = $construction->Inhabitants();
						if ($this->hasPermission($inhabitants) || $this->hasPermission($inhabitants, Relation::MARKET)) {
							$markets->add($construction);
						} else {
							if ($withMessage) {
								$this->message(EnterDeniedMessage::class, $unit)->e($construction);
								$this->message(EnterNotAllowedMessage::class, $construction)->p($unit->Name())->s($building);
							}
						}
					} else {
						if ($withMessage) {
							$this->message(EnterSiegeMessage::class, $unit)->e($construction);
						}
					}
				} else {
					if ($withMessage) {
						$this->message(EnterTooLargeMessage::class, $unit)->e($construction);
						$this->message(EnterNoSpaceMessage::class, $construction)->p($unit->Name())->s($building);
					}
				}
			}
		}
		return $markets;
	}
}
