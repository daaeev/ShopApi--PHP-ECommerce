<?php

namespace Laravel\MessageBuses;

use Project\Tests\Laravel\TestCase;
use Illuminate\Support\Facades\Queue;
use Project\Common\Services\Environment\Environment;
use Project\Infrastructure\Laravel\Jobs\ProcessCommand;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;
use Project\Infrastructure\Laravel\ApplicationMessages\Buses\QueueCommandBus;

class QueueCommandBusTest extends TestCase
{
    private readonly EnvironmentInterface $environmentService;
    private readonly Environment $environment;
    private readonly ApplicationMessageInterface $command;
    private readonly QueueCommandBus $bus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environmentService = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = $this->getMockBuilder(ApplicationMessageInterface::class)->getMock();
        $this->bus = new QueueCommandBus($this->environmentService);
    }

    public function testDispatchCommand()
    {
        $this->environmentService->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($this->environment);

        Queue::fake();
        $this->bus->dispatch($this->command);

        Queue::assertPushedOn(queue: 'commands', job: function (ProcessCommand $job) {
            return $this->command === $job->command
                && $this->environment === $job->customEnvironment;
        });
    }

    public function testCanDispatch()
    {
        $this->assertTrue($this->bus->canDispatch($this->command));
    }
}