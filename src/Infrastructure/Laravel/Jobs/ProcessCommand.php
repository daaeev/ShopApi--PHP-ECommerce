<?php

namespace Project\Infrastructure\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;

class ProcessCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private readonly ApplicationMessageInterface $command
    ) {}

    public function handle(ApplicationMessagesManager $manager): void
    {
        try {
            $manager->dispatchCommand($this->command);
        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }
}