<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Command\Teach;
use Lemuria\Engine\Fantasya\Factory\FollowTrait;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Extension\Quests;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Scenario\Fantasya\Factory\BuilderTrait;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Quest\Controller\Instructor;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;

/**
 * Act: Lehrer(…)
 */
class Teacher extends AbstractAct
{
	use BuilderTrait;
	use FollowTrait;
	use VisitationTrait;

	/**
	 * @var array<int, self>
	 */
	private static array $teacher = [];

	private Knowledge $knowledge;

	public function __construct(SetOrders $scene) {
		parent::__construct($scene);
		$id                 = $this->unit->Id()->Id();
		self::$teacher[$id] = $this;
		$this->knowledge    = new Knowledge();
	}

	public function parse(Macro $macro): static {
		parent::parse($macro);
		if ($macro->count() > 0) {
			$factory  = $this->scene->context()->Factory();
			$calculus = $this->scene->context()->getCalculus($this->unit);
			foreach ($macro->getParameters() as $parameter) {
				$talent  = $factory->talent($parameter);
				$ability = $calculus->ability($talent);
				$level   = $ability->Level();
				if ($level > 0) {
					$this->knowledge->add($ability);
					Lemuria::Log()->debug('Unit ' . $this->unit . ' can teach ' . $talent . ' up to level ' . $level . '.');
				} else {
					Lemuria::Log()->error('Unit ' . $this->unit . ' cannot offer teaching ' . $talent . '.');
				}
			}
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$this->addVisitationEffect()->Knowledge()->fill($this->knowledge);
		$quest   = $this->createQuest();
		$student = $this->getExistingFollower($this->unit)?->Leader();
		if ($student && $quest->isAssignedTo($student)) {
			$teach = new Teach(new Phrase('LEHREN ' . $student->Id()), $this->scene->context());
			$teach->setAlternative();
			State::getInstance()->injectIntoTurn($teach);
		}
		return $this;
	}

	public function getChainResult(): bool {
		return true;
	}

	protected function createQuest(): Quest {
		/** @var Quests $quests */
		$quests = $this->unit->Extensions()->init(Quests::class, fn() => new Quests($this->unit));
		/** @var Instructor $controller */
		$controller = self::createController(Instructor::class);
		foreach ($quests->getAll($controller) as $quest) {
			break;
		}
		if (!isset($quest)) {
			$quest = new Quest();
			$quest->setId(Lemuria::Catalog()->nextId(Domain::Quest));
			$quest->setOwner($this->unit)->setController($controller);
			$quests->add($quest);
		}
		$controller->setPayload($quest)->setKnowledge($this->knowledge);
		//$this->quest = $quest;
		return $quest;
	}
}
