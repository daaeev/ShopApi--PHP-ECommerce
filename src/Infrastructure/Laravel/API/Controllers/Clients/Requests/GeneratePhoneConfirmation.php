<?php

namespace Project\Infrastructure\Laravel\API\Controllers\Clients\Requests;

use Project\Common\Utils\CountryCodeIso3166;
use Project\Infrastructure\Laravel\API\Utils\ApiRequest;
use Project\Modules\Client\Commands\GeneratePhoneConfirmationCommand;

class GeneratePhoneConfirmation extends ApiRequest
{
    public function rules()
    {
        return [
            'phone' => 'required|string|phone:INTERNATIONAL,' . CountryCodeIso3166::UKRAINE,
        ];
    }

    public function getCommand(): GeneratePhoneConfirmationCommand
    {
        return new GeneratePhoneConfirmationCommand($this->validated('phone'));
    }
}