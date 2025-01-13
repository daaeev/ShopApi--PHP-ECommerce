<?php

namespace Project\Tests\Unit\Modules\Client\Consumers;

use PHPUnit\Framework\TestCase;
use Project\Common\Commands\SendSmsCommand;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Common\Services\Translator\TranslatorInterface;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Modules\Client\Consumers\SendClientConfirmationConsumer;
use Project\Modules\Client\Adapters\Events\ClientConfirmationEventsDeserializer;

class SendClientConfirmationConsumerTest extends TestCase
{
    use ClientFactory;

    private readonly TranslatorInterface $translator;
    private readonly ApplicationMessagesManager $messagesManager;
    private readonly SendClientConfirmationConsumer $consumer;

    private readonly ClientConfirmationEventsDeserializer $deserializer;
    private readonly \DateTimeImmutable $expiredAt;

    protected function setUp(): void
    {
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->messagesManager = $this->getMockBuilder(ApplicationMessagesManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = new SendClientConfirmationConsumer($this->translator, $this->messagesManager);

        $this->deserializer = $this->getMockBuilder(ClientConfirmationEventsDeserializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expiredAt = $this->getMockBuilder(\DateTimeImmutable::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSendClientConfirmation()
    {
        $this->deserializer->expects($this->once())
            ->method('getConfirmationExpiredAt')
            ->willReturn($this->expiredAt);

        $this->expiredAt->expects($this->once())
            ->method('format')
            ->with('H:i:s')
            ->willReturn($formattedExpiredAt = '15:30:10');

        $code = uniqid();
        $this->deserializer->expects($this->exactly(2))
            ->method('getConfirmationCode')
            ->willReturn($code);

        $this->translator->expects($this->once())
            ->method('translate')
            ->with(
                'client.yourConfirmationCode',
                "Ваш код підтвердження: $code. Дійсний до $formattedExpiredAt",
                ['code' => $code, 'validUntil' => $formattedExpiredAt]
            )
            ->willReturn($translatedMessage = uniqid());

        $phone = $this->generatePhone();
        $this->deserializer->expects($this->once())
            ->method('getClientPhone')
            ->willReturn($phone);

        $sendSmsCommand = new SendSmsCommand($phone, $translatedMessage);
        $this->messagesManager->expects($this->once())
            ->method('queueCommand')
            ->with($sendSmsCommand);

        call_user_func($this->consumer, $this->deserializer);
    }
}