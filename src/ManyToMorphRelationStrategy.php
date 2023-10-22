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

        $resourcesByMorphClass = $this->getResourcesByMorphClass();

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
            $collectionDictionary = $this->getCollectionDictionary($model, $attribute);

            $requestCollection = $request->all()[$requestAttribute] ?? [];

            $collectionForDetach = $this->getCollectionForDetach($requestCollection, $collectionDictionary);

            foreach ($collectionForDetach as $collectionModelForDetach) {
                $model->{$attribute}()->detach($collectionModelForDetach);
            }

            $resourcesByType = [];

            foreach ($this->field->resources as $resource) {
                $resourcesByType[$resource::uriKey()] = $resource;
            }

            foreach ($requestCollection as $index => $requestCollectionResource) {
                if ($requestCollectionResource['mode'] === 'create') {
                    $collectionModelForCreate = $this->createResourceModel($resourcesByType[$requestCollectionResource['type']], $requestCollectionResource['attributes']);

                    $model->{$attribute}()->attach($collectionModelForCreate, ['position' => $index]);
                } else if ($requestCollectionResource['mode'] === 'attach') {
                    $collectionModelForAttach = $resourcesByType[$requestCollectionResource['type']]::newModel()->newQuery()->findOrFail($requestCollectionResource['id']);

                    $this->updateResourceModel($collectionModelForAttach, $resourcesByType[$requestCollectionResource['type']], $requestCollectionResource['attributes']);

                    $model->{$attribute}()->attach($collectionModelForAttach, ['position' => $index]);
                } else if ($requestCollectionResource['mode'] === 'update') {
                    $collectionModelForUpdate = $collectionDictionary[$requestCollectionResource['id'].':'.$requestCollectionResource['type']];

                    $this->updateResourceModel($collectionModelForUpdate, $resourcesByType[$requestCollectionResource['type']], $requestCollectionResource['attributes']);

                    $model->{$attribute}()->updateExistingPivot($collectionModelForUpdate, ['position' => $index]);
                }
            }
        };
    }

    protected function getResourcesByMorphClass(): array
    {
        $resourcesByMorphClass = [];

        foreach ($this->field->resources as $resource) {
            $resourcesByMorphClass[$resource::newModel()->getMorphClass()] = $resource;
        }

        return $resourcesByMorphClass;
    }

    protected function getCollectionDictionary($model, $attribute): array
    {
        $resourcesByMorphClass = $this->getResourcesByMorphClass();

        return $model->{$attribute}()->get()
            ->keyBy(function (Model $model) use ($resourcesByMorphClass) {
                return $this->getDictionaryKey(
                    $model->getKey(),
                    $resourcesByMorphClass[$model->getMorphClass()]::uriKey()
                );
            })
            ->all();
    }

    protected function getDictionaryKey(string $id, string $type): string
    {
        return sprintf("%s:%s", $id, $type);
    }

    protected function getCollectionForDetach(array $requestCollection, array $collectionDictionary): array
    {
        $requestCollectionDictionary = [];

        foreach ($requestCollection as $requestResource) {
            $dictionaryKey = $this->getDictionaryKey($requestResource['id'], $requestResource['type']);

            $requestCollectionDictionary[$dictionaryKey] = $requestResource;
        }

        $collectionForDetach = [];

        foreach ($collectionDictionary as $dictionaryKey => $collectionModel) {
            if (! isset($requestCollectionDictionary[$dictionaryKey])) {
                $collectionForDetach[] = $collectionModel;
            }
        }

        return $collectionForDetach;
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
