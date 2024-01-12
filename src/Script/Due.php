<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use function Lemuria\direction;
use Lemuria\Lemuria;

enum Due : int
{
	case Past = -1;

	case Now = 0;

	case Future = 1;

	public static function forRound(int $round): self {
		return self::from(direction($round - Lemuria::Calendar()->Round()));
	}
}
