<?php declare(strict_types=1);

namespace Nessworthy\AmphpSimpleStateMachine;

class InvalidStartingStateException extends StateMachineException
{
    public const STARTING_STATE_MISSING = 100;
    public const STARTING_STATE_INVALID = 101;
}
