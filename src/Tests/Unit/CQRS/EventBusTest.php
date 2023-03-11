<?php

namespace Project\Tests\Unit\CQRS;

use DomainException;
use Project\Common\CQRS\Buses\EventBus;
use Project\Tests\Unit\CQRS\Commands\CommandsTrait;
use Project\Tests\Unit\CQRS\Commands\Handlers\CallableCommandHandler;
use Project\Tests\Unit\CQRS\Commands\Handlers\NonCallableCommandHandler;
use Project\Tests\Unit\CQRS\Commands\TestCommand;
use Project\Tests\Unit\CQRS\Container\NotFoundException;

class EventBusTest extends \PHPUnit\Framework\TestCase
{
    use CommandsTrait;

    public function testDispatchOneHandler()
    {
        $command = new TestCommand;
        $eventMock = $this->getMockBuilder(CallableCommandHandler::class)
            ->getMock();

        $eventMock->expects($this->once())
            ->method('__invoke')
            ->with($command);

        $bus = new EventBus(
            $this->getCommandBindings(),
            new Container\TestContainer([
                CallableCommandHandler::class => $eventMock
            ])
        );

        $bus->dispatch($command);
    }

    public function testDispatchManyHandlers()
    {
        $command = new TestCommand;
        $eventMock = $this->getMockBuilder(CallableCommandHandler::class)
            ->getMock();

        $eventMock->expects($this->exactly(2))
            ->method('__invoke')
            ->with($command);

        $bus = new EventBus(
            [
                TestCommand::class => [
                    CallableCommandHandler::class,
                    CallableCommandHandler::class
                ]
            ],
            new Container\TestContainer([
                CallableCommandHandler::class => $eventMock
            ])
        );

        $bus->dispatch($command);
    }

    public function testCantDispatch()
    {
        $this->expectException(DomainException::class);

        $bus = new EventBus(
            [],
            new Container\TestContainer([])
        );

        $bus->dispatch(new TestCommand());
    }

    public function testContainerDoesNotExistsHandler()
    {
        $this->expectException(NotFoundException::class);
        $bus = new EventBus(
            $this->getCommandBindings(),
            new Container\TestContainer([])
        );

        $bus->dispatch(new TestCommand);
    }

    public function testNonCallableHandler()
    {
        $this->expectException(DomainException::class);

        $bus = new EventBus(
            [
                TestCommand::class => NonCallableCommandHandler::class
            ],
            new Container\TestContainer([NonCallableCommandHandler::class => new NonCallableCommandHandler])
        );

        $bus->dispatch(new TestCommand);
    }
}