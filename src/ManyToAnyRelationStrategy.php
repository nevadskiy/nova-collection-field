<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class ManyToAnyRelationStrategy implements Strategy
{
    protected ManyToAnyCollection $field;

    public function setField(ManyToAnyCollection $field): void
    {
        $this->field = $field;
    }

    public function get(Model $model, string $attribute): array
    {
        $query = $model->{$attribute}();

        if ($this->field->sortByPivot) {
            $query->orderBy($this->field->sortByPivot);
        }

        $collection = $query->get();

        $resourcesByMorphClass = [];

        foreach ($this->field->resources as $resource) {
            $resourcesByMorphClass[$resource::newModel()->getMorphClass()] = $resource;
        }

        $collection = $collection->map(function (Model $model) use ($resourcesByMorphClass) {
            return new $resourcesByMorphClass[$model->getMorphClass()]($model);
        });

        $request = resolve(NovaRequest::class);

        return $collection->map(function (Resource $resource) use ($request) {
            return [
                'id' => $resource->getKey(),
                'type' => $resource::uriKey(),
                'singularLabel' => $resource::singularLabel(),
                'fields' => $resource->updateFields($request)->values()->all(),
            ];
        })
            ->values()
            ->all();
    }

    public function set(NovaRequest $request, $requestAttribute, $model, $attribute): callable
    {
        return function () use ($request, $requestAttribute, $model) {
            // @todo
        };
    }

    protected function createResourceModel(string $resourceClass, array $attributes): Model
    {
        [$model, $callbacks] = $resourceClass::fill($this->newRequestFromAttributes($attributes), $resourceClass::newModel());

        $model->save();

        foreach ($callbacks as $callback) {
            $callback();
        }

        return $model;
    }

    protected function updateResourceModel(Model $model, string $resourceClass, array $attributes): void
    {
        [$model, $callbacks] = $resourceClass::fillForUpdate($this->newRequestFromAttributes($attributes), $model);

        $model->save();

        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    protected function newRequestFromAttributes(array $attributes): NovaRequest
    {
        $requestServer = [];
        $requestFiles = [];

        foreach ($attributes as $attribute => $value) {
            if ($value instanceof UploadedFile) {
                $requestFiles[$attribute] = $value;
            } else {
                $requestServer[$attribute] = $value;
            }
        }

        $request = new NovaRequest([], $requestServer, [], [], $requestFiles);

        $request->setMethod('POST');

        return $request;
    }
}
