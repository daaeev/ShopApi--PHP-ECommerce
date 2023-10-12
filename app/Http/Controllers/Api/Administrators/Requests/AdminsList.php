<?php

namespace App\Http\Controllers\Api\Administrators\Requests;

use App\Http\Requests\ApiRequest;
use Project\Modules\Administrators\Queries\AdminsListQuery;

class AdminsList extends ApiRequest
{
    public function rules()
    {
        return [
            'page' => 'nullable|numeric|integer|min:1',
            'limit' => 'nullable|numeric|integer|min:1',
        ];
    }

    public function getQuery(): AdminsListQuery
    {
        $validated = $this->validated();
        return new AdminsListQuery(
            $validated['page'] ?? 1,
            $validated['limit'] ?? 15,
        );
    }
}