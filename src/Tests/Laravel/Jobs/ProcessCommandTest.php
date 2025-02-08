<?php

namespace Laravel\Jobs;

use Project\Tests\Laravel\TestCase;
use Project\Common\Services\Environment\Environment;
use Project\Infrastructure\Laravel\Jobs\ProcessCommand;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;

class ProcessCommandTest extends TestCase
{
    private readonly EnvironmentInterface $environmentService;
    private readonly ApplicationMessagesManager $messagesManager;

    private readonly ApplicationMessageInterface $command;
    private readonly Environment $initialEnvironment;
    private readonly Environment $customEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environmentService = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $this->app->singleton(EnvironmentInterface::class, fn () => $this->environmentService);

        $this->messagesManager = $this->getMockBuilder(ApplicationMessagesManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->singleton(ApplicationMessagesManager::class, fn () => $this->messagesManager);

        $this->command = $this->getMockBuilder(ApplicationMessageInterface::class)->getMock();

        $this->initialEnvironment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customEnvironment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessCommand()
    {
        $this->environmentService->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($this->initialEnvironment);

        $this->environmentService->expects($this->exactly(2))
            ->method('useEnvironment')
            ->willReturnMap([
                [$this->customEnvironment, null],
                [$this->initialEnvironment, null],
            ]);

        $this->messagesManager->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->command);

        ProcessCommand::dispatch($this->command, $this->customEnvironment);
    }

    public function testProcessCommandIfExceptionThrow()
    {
        $this->environmentService->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($this->initialEnvironment);

        $this->environmentService->expects($this->exactly(2))
            ->method('useEnvironment')
            ->willReturnMap([
                [$this->customEnvironment, null],
                [$this->initialEnvironment, null],
            ]);

        $this->messagesManager->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->command)
            ->willThrowException(new \DomainException);

        ProcessCommand::dispatch($this->command, $this->customEnvironment);
    }
}