<?php

namespace Project\Modules\Shopping\Discounts\Promotions\Commands\Handlers;

use Project\Common\Events\DispatchEventsTrait;
use Project\Common\Events\DispatchEventsInterface;
use Project\Modules\Shopping\Discounts\Promotions\Entity;
use Project\Modules\Shopping\Discounts\Promotions\Commands\DeletePromotionCommand;
use Project\Modules\Shopping\Discounts\Promotions\Repository\PromotionsRepositoryInterface;

class EnablePromotionHandler implements DispatchEventsInterface
{
    use DispatchEventsTrait;

    public function __construct(
        private PromotionsRepositoryInterface $promotions
    ) {}

    public function __invoke(DeletePromotionCommand $command): void
    {
        $promotion = $this->promotions->get(Entity\PromotionId::make($command->id));
        $promotion->enable();
        $this->promotions->update($promotion);
        $this->dispatchEvents($promotion->flushEvents());
    }
}