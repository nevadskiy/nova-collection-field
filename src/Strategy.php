<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

interface Strategy
{
    public function get(Model $model, string $attribute): array;

    public function set(NovaRequest $request, $requestAttribute, $model, $attribute);
}
