<?php declare(strict_types=1);

namespace Nessworthy\AmpStateMachine;

class InvalidTransitionStateException extends StateMachineException
{
    public const DESTINATION_STATE_MISSING = 200;
}
