<?php

namespace Project\Infrastructure\Laravel\API\Controllers\Clients\Requests;

use Project\Infrastructure\Laravel\API\Utils\ApiRequest;
use Project\Modules\Client\Commands\ConfirmClientPhoneCommand;

class ConfirmPhone extends ApiRequest
{
    public function rules()
    {
        return [
            'confirmationUuid' => 'required|uuid',
            'code' => 'required|string',
        ];
    }

    public function getCommand(): ConfirmClientPhoneCommand
    {
        $validated = $this->validated();
        return new ConfirmClientPhoneCommand(
            $validated['confirmationUuid'],
            $validated['code'],
        );
    }
}