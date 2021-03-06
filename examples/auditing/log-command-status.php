<?php

require_once __DIR__ . '/../bootstrap.php';

/*
 * Some setup and helpers. Real example below. ;)
 */
class ExampleCommandHandler extends Broadway\CommandHandling\CommandHandler
{
    public function handleExampleCommand(ExampleCommand $command)
    {
    }

    public function handleExampleFailureCommand(ExampleFailureCommand $command)
    {
        throw new RuntimeException('Failed!!');
    }
}

class BaseCommand
{
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}

class ExampleCommand extends BaseCommand {}
class ExampleFailureCommand extends BaseCommand {}

// Setup the system to handle commands
$commandHandler   = new ExampleCommandHandler();
$eventDispatcher  = new Broadway\EventDispatcher\EventDispatcher();
$simpleCommandBus = new Broadway\CommandHandling\SimpleCommandBus();
$commandBus       = new Broadway\CommandHandling\EventDispatchingCommandBus($simpleCommandBus, $eventDispatcher);
$commandBus->subscribe($commandHandler);

// Dependecies of auditer
$logger            = new StdoutLogger();
$commandSerializer = new Broadway\Auditing\CommandSerializer();

/*
 * The actual example!
 */

// setup the command loger
$commandAuditLogger = new Broadway\Auditing\CommandLogger($logger, $commandSerializer);

// register the command logger with the event dispatcher of the command bus
$eventDispatcher->addListener("broadway.command_handling.command_success", array($commandAuditLogger, "onCommandHandlingSuccess"));
$eventDispatcher->addListener("broadway.command_handling.command_failure", array($commandAuditLogger, "onCommandHandlingFailure"));

echo "Dispatching the command that will succeed.\n";
$command = new ExampleCommand('Hi from command!');
$commandBus->dispatch($command);

try {
    echo "Dispatching the command that will fail.\n";
    $command = new ExampleFailureCommand('Hi from failure command!');
    $commandBus->dispatch($command);
} catch (Exception $e) {
    echo "See? It failed.\n";
}
