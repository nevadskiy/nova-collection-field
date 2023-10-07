<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Laravel\Nova\Http\Requests\NovaRequest;

class OneToManyRelationStrategy implements Strategy
{
    protected OneToManyCollection $field;

    public function setField(OneToManyCollection $field): void
    {
        $this->field = $field;
    }

    public function get(Model $model, string $attribute): array
    {
        $request = resolve(NovaRequest::class);

        $collection = $model->{$attribute}()->get();

        if ($this->field->sortBy) {
            $collection = $collection->sortBy(function (Model $model) {
                return $model->getAttribute($this->field->sortBy);
            });
        }

        return $collection->map(function (Model $model) use ($request) {
            $resource = new $this->field->resourceClass($model);

            return [
                'id' => $resource->getKey(),
                'singularLabel' => $resource::singularLabel(),
                'fields' => $resource->updateFields($request)->values()->all(),
            ];
        })
            ->values()
            ->all();
    }

    public function set(NovaRequest $request, $requestAttribute, $model, $attribute): callable
    {
        return function () use ($request, $requestAttribute, $model, $attribute) {
            $collection = $request->all()[$requestAttribute] ?? [];

            $collectionById = collect($collection)
                ->filter(fn ($resource) => $resource['id'])
                ->keyBy('id');

            $relationModelsByKey = $model->{$attribute}()->get()->getDictionary();

            foreach ($relationModelsByKey as $relationModel) {
                if (! isset($collectionById[$relationModel->getKey()])) {
                    $relationModel->delete();
                }
            }

            foreach ($collection as $index => $resource) {
                if ($resource['mode'] === 'create') {
                    $this->createResourceModel($model->{$attribute}()->make(), $this->field->resourceClass, $resource['attributes'], $index);
                } else if ($resource['mode'] === 'update') {
                    $this->updateResourceModel($relationModelsByKey[$resource['id']], $this->field->resourceClass, $resource['attributes'], $index);
                }
            }
        };
    }

    protected function createResourceModel(Model $model, string $resourceClass, array $attributes, int $index): Model
    {
        [$model, $callbacks] = $resourceClass::fill($this->newRequestFromAttributes($attributes), $model);

        $this->fillSortableAttribute($model, $index);

        $model->save();

        foreach ($callbacks as $callback) {
            $callback();
        }

        return $model;
    }

    protected function updateResourceModel(Model $model, string $resourceClass, array $attributes, int $index): void
    {
        [$model, $callbacks] = $resourceClass::fillForUpdate($this->newRequestFromAttributes($attributes), $model);

        $this->fillSortableAttribute($model, $index);

        $model->save();

        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    protected function fillSortableAttribute(Model $model, int $index): void
    {
        if ($this->field->sortBy) {
            $model->setAttribute($this->field->sortBy, $index);
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
