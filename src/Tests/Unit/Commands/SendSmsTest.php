<?php

namespace Project\Tests\Unit\Commands;

use PHPUnit\Framework\TestCase;
use Project\Common\Commands\SendSmsCommand;
use Project\Common\Services\SMS\SmsSenderInterface;
use Project\Common\Commands\Handlers\SendSmsHandler;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;

class SendSmsTest extends TestCase
{
    use ContactsGenerator;

    private SmsSenderInterface $sms;
    private SendSmsHandler $handler;

    protected function setUp(): void
    {
        $this->sms = $this->getMockBuilder(SmsSenderInterface::class)->getMock();
        $this->handler = new SendSmsHandler($this->sms);
    }

    public function testSendSms()
    {
        $command = new SendSmsCommand(phone: $this->generatePhone(), message: uniqid());
        $this->sms->expects($this->once())
            ->method('send')
            ->with($command->phone, $command->message);

        call_user_func($this->handler, $command);
    }
}