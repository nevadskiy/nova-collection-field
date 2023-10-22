<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class ManyToMorphRelationStrategy implements Strategy
{
    protected ManyToMorphCollection $field;

    public function setField(ManyToMorphCollection $field): void
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
        return function () use ($request, $requestAttribute, $model, $attribute) {
            $resourcesByMorphClass = [];

            foreach ($this->field->resources as $resource) {
                $resourcesByMorphClass[$resource::newModel()->getMorphClass()] = $resource;
            }

            $collectionModels = $model->{$attribute}()->get();

            $collectionModelsDictionary = [];

            foreach ($collectionModels as $collectionModel) {
                $collectionModelsDictionary[$collectionModel->getKey().':'.$resourcesByMorphClass[$collectionModel->getMorphClass()]::uriKey()] = $collectionModel;
            }

            $requestModels = $request->all()[$requestAttribute] ?? [];

            $requestModelsDictionary = [];

            foreach ($requestModels as $requestModel) {
                $requestModelsDictionary[$requestModel['id'].':'.$requestModel['type']] = $requestModel;
            }

            $collectionModelsForDetach = [];

            foreach ($collectionModelsDictionary as $collectionKey => $collectionModel) {
                if (! isset($requestModelsDictionary[$collectionKey])) {
                    $collectionModelsForDetach[] = $collectionModel;
                }
            }

            foreach ($collectionModelsForDetach as $collectionModelForDetach) {
                $model->{$attribute}()->detach($collectionModelForDetach);
            }

            $resourcesByType = [];

            foreach ($this->field->resources as $resource) {
                $resourcesByType[$resource::uriKey()] = $resource;
            }

            foreach ($requestModels as $index => $requestModel) {
                if ($requestModel['mode'] === 'create') {
                    $collectionModelForCreate = $this->createResourceModel($resourcesByType[$requestModel['type']], $requestModel['attributes']);

                    $model->{$attribute}()->attach($collectionModelForCreate, ['position' => $index]);
                } else if ($requestModel['mode'] === 'attach') {
                    $collectionModelForAttach = $resourcesByType[$requestModel['type']]::newModel()->newQuery()->findOrFail($requestModel['id']);

                    $this->updateResourceModel($collectionModelForAttach, $resourcesByType[$requestModel['type']], $requestModel['attributes']);

                    $model->{$attribute}()->attach($collectionModelForAttach, ['position' => $index]);
                } else if ($requestModel['mode'] === 'update') {
                    $collectionModelForUpdate = $collectionModelsDictionary[$requestModel['id'].':'.$requestModel['type']];

                    $this->updateResourceModel($collectionModelForUpdate, $resourcesByType[$requestModel['type']], $requestModel['attributes']);

                    $model->{$attribute}()->updateExistingPivot($collectionModelForUpdate, ['position' => $index]);
                }
            }
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
