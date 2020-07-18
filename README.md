# Simple State Machine (for amphp)

## Requirements

* php >= 7.4
* Composer

## Install

```bash
composer require nessworthy/amphp-simple-state-machine
```

## Usage Example

```php
// Define your states - they must implement the State interface. 
$exampleState = new class implements \Nessworthy\AmphpSimpleStateMachine\State {
    public function execute(stdClass $stateData) : \Amp\Promise {
        $stateData->number += 1;
        return new \Amp\Success($stateData);
    }
};

$machine = new Nessworthy\AmphpSimpleStateMachine\MachineRunner();

// Register your state objects with the runner.
$machine->registerState($exampleState, 'MyFirstState');
$machine->registerState($exampleState, 'MySecondState');
$machine->registerState($exampleState, 'MyThirdState');

// Add a simple transition to another state.
$machine->registerTransition('MyFirstState', 'MySecondState');

// Add a transition with a guarding predicate.
$machine->registerTransition('MySecondState', 'MyThirdState', function($data) {
    return $data->number > 3;
});

// Transitions are evaluated in order of registration.
// This will only be used if the above transition isn't.
$machine->registerTransition('MySecondState', 'MyFirstState');

// Set your initial state.
$machine->setStartingState('MyFirstState');

$initialData = new \stdClass();
$initialData->number = 0;

// Give it a whirl.
$resultingData = yield $machine->execute($initialData); // { "number" => 5 }

// Sequence of events:
// Transition: START -> MyFirstState
// Execution: MyFirstState (0 -> 1)
// Transition: MyFirstState -> MySecondState
// Execution: MySecondState (1 -> 2)
// Check Transition Guard: MySecondState -> MyThirdState (False)
// Transition: MySecondState -> MyFirstState
// Execution: MyFirstState (2 -> 3)
// Transition: MyFirstState -> MySecondState
// Execution: MySecondState (3 -> 4)
// Check Transition Guard: MySecondState -> MyThirdState (True)
// Transition: MySecondState -> MyThirdState
// Execution: MyThirdState (4 -> 5)
// Transition: MyThirdState -> END 
```
