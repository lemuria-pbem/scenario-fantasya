<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Engine\Fantasya\Effect\WelcomeVisitor;
use Lemuria\Engine\Fantasya\Factory\Model\Buzz;
use Lemuria\Engine\Fantasya\Factory\Model\Buzzes;
use Lemuria\Engine\Fantasya\Factory\Scenario\Visitation as VisitationInterface;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Commodity;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Composition\HerbAlmanac;
use Lemuria\Model\Fantasya\Composition\Scroll;
use Lemuria\Model\Fantasya\Composition\Spellbook;
use Lemuria\Model\Fantasya\Extension\Quests;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\MagicRing;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Model\Fantasya\Unicum;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Engine\Event\TravelCommands;
use Lemuria\Scenario\Fantasya\Factory\BuilderTrait as ScenarioBuilderTrait;
use Lemuria\Scenario\Fantasya\Quest\Controller\Instructor;
use Lemuria\Scenario\Fantasya\Quest\Controller\SellUnicum;
use Lemuria\Scenario\Fantasya\Quest\Payload;
use Lemuria\Scenario\Fantasya\Quest\Status;
use Lemuria\Scenario\Fantasya\Script\Act\Demand;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\Scenario\Fantasya\TranslateTrait;
use Lemuria\SingletonSet;

class Visitation implements VisitationInterface
{
	use BuilderTrait;
	use ScenarioBuilderTrait;
	use TranslateTrait;
	use VisitationTrait;

	protected Unit $visitor;

	protected SingletonSet $demand;

	protected Knowledge $knowledge;

	protected ?Quest $passage = null;

	protected Buzzes $messages;

	/** @var array<int, self> */
	private static array $visitation = [];

	private static ?Commodity $silver = null;

	public static function register(Unit $unit, WelcomeVisitor $effect): self {
		$id = $unit->Id()->Id();
		if (!isset(self::$visitation[$id])) {
			$visitation            = new self($unit);
			self::$visitation[$id] = $visitation;
			$effect->setVisitation($visitation);
		}
		return self::$visitation[$id];
	}

	protected function __construct(protected readonly Unit $unit) {
		$this->demand    = new SingletonSet();
		$this->knowledge = new Knowledge();
		$this->messages  = new Buzzes();
		if (!self::$silver) {
			self::$silver = self::createCommodity(Silver::class);
		}
		$this->initDictionary();
	}

	public function Demand(): SingletonSet {
		return $this->demand;
	}

	public function Knowledge(): Knowledge {
		return $this->knowledge;
	}

	public function from(Unit $unit): Buzzes {
		$this->visitor = $unit;
		$this->messages->clear();
		Lemuria::Log()->debug($this->unit . ' is visited by ' . $unit . '.');
		$this->makeUnicumOffers();
		$this->makeInstructorOffer();
		$this->makePassageDemand();
		return $this->messages;
	}

	public function setPassage(Quest $quest): static {
		$this->passage = $quest;
		return $this;
	}

	protected function makeUnicumOffers(): void {
		foreach ($this->visitor->Treasury() as $unicum) {
			$composition = $unicum->Composition();
			if ($this->demand->offsetExists($composition)) {
				$name = $unicum->Name();
				if (!$name) {
					$name = (string)$unicum->Id();
				}
				$payment = $this->calculateSilverValue($unicum);
				if ($payment->Count() > 0) {
					$quest = $this->createSellUnicumQuest($unicum, $payment);
					$this->offerQuestTo($quest, $this->visitor);
					$message = $this->dictionary->get('demand.unicum');
					$message = $this->replaceItem($message, '$payment', $payment);
					$message = $this->translateReplace($message, '$composition', $composition);
					$this->messages->add(new Buzz($this->translateReplace($message, '$unicum', $name)));
					Lemuria::Log()->debug($this->unit . ' makes an offer for ' . $composition . ' ' . $unicum->Id() . '.');
					Demand::getInstance($this->unit)?->waitForAcceptance();
					TravelCommands::cancelTravelFor($this->unit);
				} else {
					Lemuria::Log()->debug($composition . ' ' . $unicum->Id() . ' has no value for us.');
				}
			}
		}
	}

	protected function makeInstructorOffer(): void {
		if (!$this->knowledge->isEmpty()) {
			$quest = $this->createInstructorQuest();
			if ($quest) {
				$this->offerQuestTo($quest, $this->visitor);
				Lemuria::Log()->debug($this->unit . ' applies as teacher.');
			}
		}
	}

	protected function makePassageDemand(): void {
		if ($this->passage) {
			$this->offerQuestTo($this->passage, $this->visitor);
			Lemuria::Log()->debug($this->unit . ' demands a passage.');
		}
	}

	protected function calculateSilverValue(Unicum $unicum): Quantity {
		$composition = $unicum->Composition();
		$value = match (true) {
			$composition instanceof Scroll      => $this->getMaterialValue($composition) + $this->getMaterialValue($composition->Spell()),
			$composition instanceof Spellbook   => $this->getMaterialValue($composition) + $this->getMaterialValue($composition->Spells()),
			$composition instanceof HerbAlmanac => $this->getMaterialValue($composition) + $this->getMaterialValue($composition->HerbalBook()),
			$composition instanceof MagicRing   => $this->getMaterialValue($composition) + $this->getMaterialValue($composition->Enchantment()),
			default                             => 0
		};
		return new Quantity(self::$silver, $value);
	}

	protected function createSellUnicumQuest(Unicum $unicum, Quantity $payment): Quest {
		/** @var Quests $quests */
		$quests = $this->unit->Extensions()->init(Quests::class, fn() => new Quests($this->unit));
		/** @var SellUnicum $controller */
		$controller = self::createController(SellUnicum::class);
		foreach ($quests->getAll($controller) as $quest) {
			if ($controller->setPayload($quest)->Unicum() === $unicum) {
				$controller->setPayment($payment);
				return $quest;
			}
		}

		$quest = new Quest();
		$quest->setId(Lemuria::Catalog()->nextId(Domain::Quest));
		$quest->setOwner($this->unit)->setController($controller);
		$controller->setPayload($quest)->setUnicum($unicum)->setPayment($payment)->setSeller($unicum->Collector());
		$quests->add($quest);
		return $quest;
	}

	protected function createInstructorQuest(): ?Quest {
		/** @var Quests $quests */
		$quests = $this->unit->Extensions()->init(Quests::class, fn() => new Quests($this->unit));
		/** @var Instructor $controller */
		$controller = self::createController(Instructor::class);

		if ($quests->isEmpty()) {
			$quest = new Quest();
			$quest->setId(Lemuria::Catalog()->nextId(Domain::Quest));
			$quest->setOwner($this->unit)->setController($controller);
			$controller->setPayload($quest)->setKnowledge($this->knowledge);
			$quests->add($quest);
			return $quest;
		}

		foreach ($quests->getAll($controller) as $quest) {
			/** @var Payload $payload */
			$payload = $quest->Payload();
			if ($payload->hasAnyStatus(Status::Assigned)) {
				continue;
			}
			$controller->setPayload($quest)->setKnowledge($this->knowledge);
			return $quest;
		}
		return null;
	}
}
