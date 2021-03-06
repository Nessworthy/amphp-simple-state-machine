<?php declare(strict_types=1);

namespace Nessworthy\AmphpSimpleStateMachine;

use Amp\Promise;
use stdClass;

interface State
{
    public function execute(stdClass $stateData): Promise;
}
