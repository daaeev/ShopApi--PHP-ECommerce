<?php

namespace Project\Infrastructure\Laravel\API\Controllers\Clients\Requests;

use Project\Infrastructure\Laravel\API\Utils\ApiRequest;
use Project\Modules\Client\Commands\RefreshPhoneConfirmationCommand;

class RefreshPhoneConfirmation extends ApiRequest
{
    public function rules()
    {
        return [
            'confirmationUuid' => 'required|uuid',
        ];
    }

    public function getCommand(): RefreshPhoneConfirmationCommand
    {
        return new RefreshPhoneConfirmationCommand($this->validated('confirmationUuid'));
    }
}