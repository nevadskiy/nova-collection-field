<?php

namespace Nevadskiy\Nova\Collection\Http\Controllers;

use Laravel\Nova\Http\Requests\NovaRequest;

class AttachableResourceController
{
    public function index(NovaRequest $request)
    {
        return $request->newResource()
            ->availableFields($request)
            ->findFieldByAttribute($request->field, function () {
                abort(404);
            })
            ->paginateAttachableResources($request, $request->attachable);
    }
}
