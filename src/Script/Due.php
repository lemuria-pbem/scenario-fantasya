<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use function Lemuria\direction;
use Lemuria\Lemuria;

enum Due : int
{
	case PAST = -1;

	case NOW = 0;

	case FUTURE = 1;

	public static function forRound(int $round): self {
		return self::from(direction($round - Lemuria::Calendar()->Round()));
	}
}
