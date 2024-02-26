<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Engine\Fantasya\Effect\WelcomeVisitor;
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
use Lemuria\Model\Fantasya\MagicRing;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Model\Fantasya\Unicum;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Factory\BuilderTrait as ScenarioBuilderTrait;
use Lemuria\Scenario\Fantasya\Quest\Controller\SellUnicum;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\Scenario\Fantasya\TranslateTrait;
use Lemuria\SingletonSet;
use Lemuria\StringList;

class Visitation implements VisitationInterface
{
	use BuilderTrait;
	use ScenarioBuilderTrait;
	use TranslateTrait;
	use VisitationTrait;

	protected Unit $visitor;

	protected SingletonSet $demand;

	protected StringList $messages;

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
		$this->demand   = new SingletonSet();
		$this->messages = new StringList();
		if (!self::$silver) {
			self::$silver = self::createCommodity(Silver::class);
		}
		$this->initDictionary();
	}

	public function Demand(): SingletonSet {
		return $this->demand;
	}

	public function from(Unit $unit): StringList {
		$this->visitor = $unit;
		Lemuria::Log()->debug($this->unit . ' is visited by ' . $unit . '.');
		$this->makeUnicumOffers();
		return $this->messages;
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
					$message          = $this->dictionary->get('demand.unicum');
					$message          = $this->replaceItem($message, '$payment', $payment);
					$message          = $this->translateReplace($message, '$composition', $composition);
					$this->messages[] = $this->translateReplace($message, '$unicum', $name);
					Lemuria::Log()->debug($this->unit . ' makes an offer for ' . $composition . ' ' . $unicum->Id() . '.');
				} else {
					Lemuria::Log()->debug($composition . ' ' . $unicum->Id() . ' has no value for us.');
				}
			}
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
		$quest->setController($controller);
		$controller->setPayload($quest)->setUnicum($unicum)->setPayment($payment)->setSeller($unicum->Collector());
		$quests->add($quest);
		return $quest;
	}
}
