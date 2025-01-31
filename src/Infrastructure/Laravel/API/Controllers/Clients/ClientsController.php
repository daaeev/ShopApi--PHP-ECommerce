<?php

namespace Project\Infrastructure\Laravel\API\Controllers\Clients;

use Project\Modules\Client\Commands\LogoutClientCommand;
use Project\Modules\Client\Queries\GetAuthenticatedClientQuery;
use Project\Infrastructure\Laravel\API\Controllers\BaseApiController;

class ClientsController extends BaseApiController
{
    public function generateConfirmation(Requests\GeneratePhoneConfirmation $request)
    {
        $confirmationUuid = $this->dispatchCommand($request->getCommand());
        return $this->success(['uuid' => $confirmationUuid], message: 'Confirmation generated');
    }

    public function refreshConfirmation(Requests\RefreshPhoneConfirmation $request)
    {
        $this->dispatchCommand($request->getCommand());
        return $this->success(message: 'Confirmation expired at refreshed');
    }

    public function confirmPhone(Requests\ConfirmPhone $request)
    {
        $this->dispatchCommand($request->getCommand());
        return $this->success(message: 'Phone confirmed! You are authenticated');
    }

    public function logout()
    {
        $command = new LogoutClientCommand;
        $this->dispatchCommand($command);
        return $this->success(message: 'Bye!');
    }

    public function get(Requests\GetClient $request)
    {
        return $this->success($this->dispatchQuery($request->getQuery()));
    }

    public function getAuthenticated()
    {
        $query = new GetAuthenticatedClientQuery;
        return $this->success($this->dispatchQuery($query));
    }

    public function list(Requests\GetClients $request)
    {
        return $this->success($this->dispatchQuery($request->getQuery()));
    }
}