<?php declare(strict_types=1);

namespace Nessworthy\AmpStateMachine;

use Amp\Promise;
use Amp\Success;
use stdClass;
use Throwable;
use function Amp\call;

class MachineRunner
{
    /**
     * @var State[]
     */
    private array $states = [];
    private array $transitions = [];
    private ?string $start = null;

    public function registerState(State $state, string $stateName): void
    {
        $this->states[$stateName] = $state;
        $this->transitions[$stateName] = [];
    }

    public function registerTransition(string $from, string $to, callable $predicate = null): void
    {
        $this->transitions[$from][] = ['to' => $to, 'guard' => $predicate];
    }

    public function setStartingState(string $startingState): void
    {
        $this->start = $startingState;
    }

    public function execute(stdClass $initialData): Promise
    {
        if (is_null($this->start)) {
            throw new InvalidStartingStateException(
                'Starting state was not set - please use setStartingState(...) before execution.',
                InvalidStartingStateException::STARTING_STATE_MISSING
            );
        }

        if (!isset($this->states[$this->start])) {
            $message = sprintf(
                'Starting state given (%s) matches no registered states. List of registered states: %s',
                $this->start,
                implode(', ', array_keys($this->states))
            ); // Exception message is created this way, since doing it inline makes code coverage mark it as missed.
            throw new InvalidStartingStateException(
                $message,
                InvalidStartingStateException::STARTING_STATE_INVALID
            );
        }

        $initialData = clone $initialData;

        return call(function() use ($initialData) {
            $stateName = $this->start;

            $data = $initialData;

            while ($stateName) {

                if (!isset($this->states[$stateName])) {
                    throw new InvalidTransitionStateException(
                        'Tried to transition to unregistered state "' . $stateName . '".',
                        InvalidTransitionStateException::DESTINATION_STATE_MISSING
                    );
                }

                $state = $this->states[$stateName];

                try {
                    $data = yield $state->execute($data);
                } catch (Throwable $throwable) {
                    throw new StateMachineExecutionException(
                        'Throwable caught during execution: ' . $stateName . ' - ' . $throwable->getMessage(),
                        $throwable->getCode(),
                        $throwable
                    );
                }

                $nextStateName = null;

                foreach ($this->transitions[$stateName] as $transition) {
                    $guardData = clone($data);
                    if (!$transition['guard'] || $transition['guard']($guardData)) {
                        $nextStateName = $transition['to'];
                        break;
                    }
                }

                $stateName = $nextStateName;
                $data = clone $data;
            }

            return new Success($data);

        });
    }

}
