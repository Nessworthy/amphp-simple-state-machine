<?php declare(strict_types=1);

namespace Nessworthy\AmpStateMachine\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Nessworthy\AmpStateMachine\MachineRunner;
use Nessworthy\AmpStateMachine\State;
use Nessworthy\AmpStateMachine\StateMachineExecutionException;
use RuntimeException;
use stdClass;

class MachineRunnerBadStateTest extends AsyncTestCase
{
    private State $simpleState;
    private stdClass $simpleInitialState;
    private MachineRunner $runner;

    protected function setUp(): void
    {
        $this->setTimeout(1000);

        $this->runner = new MachineRunner();

        $this->simpleInitialState = new stdClass();

        $this->simpleState = new class implements State {
            public function execute(stdClass $stateData): Promise
            {
                throw new RuntimeException('Oh no, something bad happened :(');
            }
        };

        parent::setUp();
    }

    public function testStateThrowingExceptionIsWrappedWithStateMachineException(): Promise
    {
        $this->expectException(StateMachineExecutionException::class);

        $this->runner->registerState($this->simpleState, 'SimpleState');
        $this->runner->setStartingState('SimpleState');

        return $this->runner->execute($this->simpleInitialState);
    }
}
