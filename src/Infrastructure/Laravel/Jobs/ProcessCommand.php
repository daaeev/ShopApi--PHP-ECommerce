<?php

namespace Project\Infrastructure\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Project\Common\Services\Environment\Environment;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;

class ProcessCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public readonly ApplicationMessageInterface $command,
        public readonly Environment $customEnvironment,
    ) {}

    public function handle(
        EnvironmentInterface $environment,
        ApplicationMessagesManager $manager
    ): void {
        $currentEnvironment = $environment->getEnvironment();
        $environment->useEnvironment($this->customEnvironment);

        try {
            $manager->dispatchCommand($this->command);
        } catch (\Throwable $e) {
            $this->fail($e);
        } finally {
            $environment->useEnvironment($currentEnvironment);
        }
    }
}