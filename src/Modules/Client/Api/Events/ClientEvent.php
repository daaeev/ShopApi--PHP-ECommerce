<?php

namespace Project\Modules\Client\Api\Events;

enum ClientEvent: string
{
    case CREATED = 'clients.created';
    case UPDATED = 'clients.updated';
    case CONFIRMATION_CREATED = 'clients.confirmationCreated';
    case CONFIRMATION_REFRESHED = 'clients.confirmationRefreshed';
    case CONFIRMATION_REMOVED = 'clients.confirmationRemoved';
}
