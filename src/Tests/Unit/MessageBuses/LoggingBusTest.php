<?php

namespace Project\Tests\Unit\MessageBuses;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Project\Common\ApplicationMessages\Buses\CompositeRequestBus;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;
use Project\Common\ApplicationMessages\Buses\Decorators\LoggingBusDecorator;

class LoggingBusTest extends TestCase
{
    public function testDispatch()
    {
        $command = $this->getMockBuilder(ApplicationMessageInterface::class)->getMock();
        $decoratedBusMock = $this->getMockBuilder(CompositeRequestBus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $decoratedBusMock->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn('Success');

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'Dispatch message: ' . $command::class . ' with params ' . json_encode(get_object_vars($command));
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with($message);

        $loggingBus = new LoggingBusDecorator($decoratedBusMock, $loggerMock);
        $this->assertSame('Success', $loggingBus->dispatch($command));
    }
}