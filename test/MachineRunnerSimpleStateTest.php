<?php declare(strict_types=1);

namespace Nessworthy\AmpStateMachine\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Amp\Success;
use Generator;
use Nessworthy\AmpStateMachine\InvalidTransitionStateException;
use Nessworthy\AmpStateMachine\MachineRunner;
use Nessworthy\AmpStateMachine\InvalidStartingStateException;
use Nessworthy\AmpStateMachine\State;
use stdClass;

class MachineRunnerSimpleStateTest extends AsyncTestCase
{
    private State $simpleState;
    private stdClass $simpleStartingData;
    private MachineRunner $runner;

    protected function setUp(): void
    {
        $this->setTimeout(1000);

        $this->runner = new MachineRunner();

        $this->simpleStartingData = new stdClass();
        $this->simpleStartingData->i = 0;

        $this->simpleState = new class implements State {
            public function execute(stdClass $stateData): Promise
            {
                ++$stateData->i;
                return new Success($stateData);
            }
        };
        parent::setUp();
    }

    public function testStartingMachineRunnerWithoutInitialStateThrowsInvalidStartingStateException(): ?Generator
    {
        $this->expectException(InvalidStartingStateException::class);
        $this->expectExceptionCode(InvalidStartingStateException::STARTING_STATE_MISSING);

        yield $this->runner->execute(new stdClass());
    }

    public function testStartingMachineRunnerWithBadStartingStateThrowsInvalidStartingStateException(): ?Generator
    {
        $this->expectException(InvalidStartingStateException::class);
        $this->expectExceptionCode(InvalidStartingStateException::STARTING_STATE_INVALID);

        $this->runner->setStartingState('NonExistentState');

        yield $this->runner->execute(new stdClass());
    }

    public function testUseOfSingleSimpleState(): ?Generator
    {
        $this->runner->registerState($this->simpleState, 'SimpleState');
        $this->runner->setStartingState('SimpleState');

        $result = yield $this->runner->execute($this->simpleStartingData);

        self::assertIsObject($result, 'Resulting state is not an object.');
        self::assertObjectHasAttribute('i', $result, 'Resulting state does not have expected attribute.');
        self::assertEquals(1, $result->i, 'Resulting state does not have expected value incremented once.');
    }

    public function testSecondSimpleStateIsNotExecuted(): ?Generator
    {
        $this->runner->registerState($this->simpleState, 'SimpleState');
        $this->runner->registerState($this->simpleState, 'SecondSimpleState');

        $this->runner->setStartingState('SimpleState');

        $result = yield $this->runner->execute($this->simpleStartingData);

        self::assertIsObject($result, 'Resulting state is not an object.');
        self::assertObjectHasAttribute('i', $result, 'Resulting state does not have expected attribute.');
        self::assertEquals(1, $result->i, 'Resulting state does not have expected value incremented once.');
    }

    public function testTwoSimpleStatesWithSimpleTransition(): ?Generator
    {
        $this->runner->registerState($this->simpleState, 'FirstSimpleState');
        $this->runner->registerState($this->simpleState, 'SecondSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $this->runner->registerTransition('FirstSimpleState', 'SecondSimpleState');

        $result = yield $this->runner->execute($this->simpleStartingData);

        self::assertIsObject($result, 'Resulting state is not an object.');
        self::assertObjectHasAttribute('i', $result, 'Resulting state does not have expected attribute.');
        self::assertEquals(2, $result->i, 'Resulting state does not have expected value incremented once.');
    }

    public function testStateTransitioningIntoNonExistentStateThrowsInvalidTransitionException(): Promise
    {
        $this->expectException(InvalidTransitionStateException::class);
        $this->expectExceptionCode(InvalidTransitionStateException::DESTINATION_STATE_MISSING);

        $this->runner->registerState($this->simpleState, 'FirstSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $this->runner->registerTransition('FirstSimpleState', 'InvalidState');

        return $this->runner->execute($this->simpleStartingData);
    }

    public function testStartAndFinalDataAreDifferent(): ?Generator
    {
        $data = $this->simpleStartingData;

        $this->runner->registerState($this->simpleState, 'FirstSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $promise = $this->runner->execute($data);

        $result = yield $promise;

        self::assertNotSame($data, $result, 'Starting and final state data are the same.');
    }

    public function testChangingInitialDataBeforePassingPriorityDoesntChangeDataReceivedByStartingState(): ?Generator
    {
        $data = $this->simpleStartingData;

        $this->runner->registerState($this->simpleState, 'FirstSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $promise = $this->runner->execute($data);

        $data->i = 10;

        $result = yield $promise;

        self::assertEquals(1, $result->i, 'Resulting state was changed outside of the state machine before execution.');
    }

    public function testDataPassedThroughBetweenStatesIsNeverTheSameReference(): ?Generator
    {
        $data = $this->simpleStartingData;

        $firstSimpleState = new class implements State {
            public stdClass $data;

            public function execute(stdClass $stateData): Promise
            {
                $this->data = $stateData;
                ++$stateData->i;
                return new Success($stateData);
            }

        };

        $secondSimpleState = new class implements State {
            public stdClass $data;

            public function execute(stdClass $stateData): Promise
            {
                $this->data = $stateData;
                ++$stateData->i;
                return new Success($stateData);
            }

        };


        $this->runner->registerState($firstSimpleState, 'FirstSimpleState');
        $this->runner->registerState($secondSimpleState, 'SecondSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $this->runner->registerTransition('FirstSimpleState', 'SecondSimpleState');

        $result = yield $this->runner->execute($data);

        self::assertNotSame($this->simpleStartingData, $firstSimpleState->data, 'Starting data and first state data are the same.');
        self::assertNotSame($firstSimpleState->data, $secondSimpleState->data, 'First state data and second state data are the same.');
        self::assertNotSame($secondSimpleState->data, $result, 'Second state data and returned state data are the same.');
    }

    public function testSimpleStateTransitionGuardWorks(): ?Generator
    {
        $this->runner->registerState($this->simpleState, 'FirstSimpleState');
        $this->runner->registerState($this->simpleState, 'SecondSimpleState');
        $this->runner->registerState($this->simpleState, 'ThirdSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $this->runner->registerTransition('FirstSimpleState', 'SecondSimpleState', static function() {
            return true;
        });

        $this->runner->registerTransition('SecondSimpleState', 'ThirdSimpleState', static function() {
            return false;
        });

        $result = yield $this->runner->execute($this->simpleStartingData);

        self::assertNotEquals(1, $result->i, 'First true guard prevented state transition.');
        self::assertNotEquals(3, $result->i, 'Second false guard did not prevent state transition.');
        self::assertEquals(2, $result->i, 'Unexpected final evaluation when testing guards.');
    }

    public function testSimpleStateTransitionGuardCannotModifyStateData(): ?Generator
    {
        $this->runner->registerState($this->simpleState, 'FirstSimpleState');
        $this->runner->registerState($this->simpleState, 'SecondSimpleState');

        $this->runner->setStartingState('FirstSimpleState');

        $this->runner->registerTransition('FirstSimpleState', 'SecondSimpleState', static function($data) {
            $data->i = 100;
            return true;
        });

        $result = yield $this->runner->execute($this->simpleStartingData);

        self::assertEquals(2, $result->i, 'Guard was able to modify data before hitting second state.');
    }

}
