<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class ManyToMorphRelationStrategy implements Strategy
{
    use InteractsWithResourceFields;

    protected ManyToMorphCollection $field;

    public function setField(ManyToMorphCollection $field): void
    {
        $this->field = $field;
    }

    public function get(Model $model, string $attribute): array
    {
        $collection = $model->{$attribute}()->get();

        if ($this->field->sortBy) {
            $collection = $collection->sortBy(function (Model $model) {
                // @todo support custom "pivot" accessor
                return $model->pivot->getAttribute($this->field->sortBy);
            });
        }

        $resourceClassesByMorphClass = $this->getResourceClassesByMorphClass();

        $collection = $collection->map(function (Model $model) use ($resourceClassesByMorphClass) {
            return new $resourceClassesByMorphClass[$model->getMorphClass()]($model);
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

            $modelsForDetach = $this->getModelsForDetach($requestCollection, $collectionDictionary);

            foreach ($modelsForDetach as $modelForDetach) {
                $model->{$attribute}()->detach($modelForDetach);
            }

            $resourceClassesByType = [];

            foreach ($this->field->resources as $resource) {
                $resourceClassesByType[$resource::uriKey()] = $resource;
            }

            foreach ($requestCollection as $index => $requestResource) {
                if ($requestResource['mode'] === 'create') {
                    $modelForCreate = $this->createResourceModel($resourceClassesByType[$requestResource['type']], $requestResource['attributes']);

                    $model->{$attribute}()->attach($modelForCreate, $this->getPivotAttributes($index));
                } else if ($requestResource['mode'] === 'attach') {
                    $modelForAttach = $resourceClassesByType[$requestResource['type']]::newModel()->newQuery()->findOrFail($requestResource['id']);

                    $this->updateResourceModel($modelForAttach, $resourceClassesByType[$requestResource['type']], $requestResource['attributes']);

                    $model->{$attribute}()->attach($modelForAttach, $this->getPivotAttributes($index));
                } else if ($requestResource['mode'] === 'update') {
                    $modelForUpdate = $collectionDictionary[$this->getDictionaryKey($requestResource['id'], $requestResource['type'])];

                    $this->updateResourceModel($modelForUpdate, $resourceClassesByType[$requestResource['type']], $requestResource['attributes']);

                    $model->{$attribute}()->updateExistingPivot($modelForUpdate, $this->getPivotAttributes($index));
                }
            }
        };
    }

    protected function getResourceClassesByMorphClass(): array
    {
        $resourceClassesByMorphClass = [];

        foreach ($this->field->resources as $resourceClass) {
            $resourceClassesByMorphClass[$resourceClass::newModel()->getMorphClass()] = $resourceClass;
        }

        return $resourceClassesByMorphClass;
    }

    protected function getCollectionDictionary($model, $attribute): array
    {
        $resourceClassesByMorphClass = $this->getResourceClassesByMorphClass();

        return $model->{$attribute}()->get()
            ->keyBy(function (Model $model) use ($resourceClassesByMorphClass) {
                return $this->getDictionaryKey(
                    $model->getKey(),
                    $resourceClassesByMorphClass[$model->getMorphClass()]::uriKey()
                );
            })
            ->all();
    }

    protected function getDictionaryKey(string $id, string $type): string
    {
        return sprintf("%s:%s", $id, $type);
    }

    protected function getModelsForDetach(array $requestCollection, array $collectionDictionary): array
    {
        $requestCollectionDictionary = [];

        foreach ($requestCollection as $requestResource) {
            $dictionaryKey = $this->getDictionaryKey($requestResource['id'], $requestResource['type']);

            $requestCollectionDictionary[$dictionaryKey] = $requestResource;
        }

        $modelsForDetach = [];

        foreach ($collectionDictionary as $dictionaryKey => $collectionModel) {
            if (! isset($requestCollectionDictionary[$dictionaryKey])) {
                $modelsForDetach[] = $collectionModel;
            }
        }

        return $modelsForDetach;
    }

    protected function getPivotAttributes(int $index): array
    {
        if ($this->field->sortBy) {
            return [$this->field->sortBy => $index];
        }

        return [];
    }
}
