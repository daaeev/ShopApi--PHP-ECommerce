<?php

namespace Project\Modules\Client\Consumers;

use Project\Common\Commands\SendSmsCommand;
use Project\Common\Services\Translator\TranslatorInterface;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Modules\Client\Adapters\Events\ClientConfirmationEventsDeserializer;

class SendClientConfirmationConsumer
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ApplicationMessagesManager $messagesManager,
    ) {}

    public function __invoke(ClientConfirmationEventsDeserializer $event): void
    {
        $expiredAt = $event->getConfirmationExpiredAt()->format('H:i:s');
        $message = $this->translator->translate(
            key: 'client.yourConfirmationCode',
            default: "Ваш код підтвердження: {$event->getConfirmationCode()}. Дійсний до $expiredAt",
            params: ['code' => $event->getConfirmationCode(), 'validUntil' => $expiredAt]
        );

        $command = new SendSmsCommand($event->getClientPhone(), $message);
        $this->messagesManager->queueCommand($command);
    }
}