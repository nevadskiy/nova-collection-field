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
        // TODO: Implement get() method.
    }

    public function set(NovaRequest $request, $requestAttribute, $model, $attribute): callable
    {
        return function () use ($request, $requestAttribute, $model, $attribute) {
            $collection = $request->all()[$requestAttribute] ?? [];

            dd($collection);

            // @todo handle deletes.

            $relationModelsByKeys = $model->{$attribute}()->get()->getDictionary();

//            $diff = $relationModelsByKeys->diff(
//                collect($collection)->map(fn (array $resource) => $resource['id'])->filter()
//            );

            foreach ($collection as $resource) {
                if ($resource['mode'] === 'create') {
                    $this->createResourceModel($model->{$attribute}()->make(), $this->field->resourceClass, $resource['attributes']);
                } else if ($resource['mode'] === 'update') {
                    $this->updateResourceModel($relationModelsByKeys[$resource['id']], $this->field->resourceClass, $resource['attributes']);
                }
            }
        };
    }

    protected function createResourceModel(Model $model, string $resourceClass, array $attributes): Model
    {
        [$model, $callbacks] = $resourceClass::fill($this->newRequestFromAttributes($attributes), $model);

        // @todo handle this.
        $model->position = 0;

        $model->save();

        foreach ($callbacks as $callback) {
            $callback();
        }

        return $model;
    }

    protected function updateResourceModel(Model $model, string $resourceClass, array $attributes): void
    {
        [$model, $callbacks] = $resourceClass::fillForUpdate($this->newRequestFromAttributes($attributes), $model);

        // @todo handle this.
        $model->position = 0;

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
