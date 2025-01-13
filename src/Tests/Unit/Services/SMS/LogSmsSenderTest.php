<?php

namespace Project\Tests\Unit\Services\SMS;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Project\Common\Services\SMS\LogSmsSender;
use Project\Common\Services\SMS\SmsSenderInterface;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;

class LogSmsSenderTest extends TestCase
{
    use ContactsGenerator;

    private LoggerInterface $logger;
    private SmsSenderInterface $sms;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->sms = new LogSmsSender($this->logger);
    }

    public function testSendSms()
    {
        $phone = $this->generatePhone();
        $message = uniqid();
        $this->logger->expects($this->once())
            ->method('info')
            ->with("Sending SMS to $phone: $message");

        $this->sms->send($phone, $message);
    }
}