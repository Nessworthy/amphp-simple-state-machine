<?php declare(strict_types=1);

namespace Nessworthy\AmphpSimpleStateMachine\Test;

use Amp\PHPUnit\AsyncTestCase;
use Nessworthy\AmphpSimpleStateMachine\MachineRunner;
use Nessworthy\AmphpSimpleStateMachine\InvalidStartingStateException;
use stdClass;

class MachineRunnerBasicTest extends AsyncTestCase
{
    public function testStartingMachineRunnerWithoutStartingStateThrowsInvalidStartingStateException(): void
    {
        $this->expectException(InvalidStartingStateException::class);
        $this->expectExceptionCode(InvalidStartingStateException::STARTING_STATE_MISSING);

        $runner = new MachineRunner();
        $runner->execute(new stdClass());
    }

    public function testStartingMachineRunnerWithBadStartingStateThrowsInvalidStartingStateException(): void
    {
        $this->expectException(InvalidStartingStateException::class);
        $this->expectExceptionCode(InvalidStartingStateException::STARTING_STATE_INVALID);

        $runner = new MachineRunner();
        $runner->setStartingState('NonExistentState');
        $runner->execute(new stdClass());
    }
}
