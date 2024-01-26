<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Quest;

enum Status : string
{
	case None = 'none';

	case Assigned = 'assigned';

	case Completed = 'completed';
}
