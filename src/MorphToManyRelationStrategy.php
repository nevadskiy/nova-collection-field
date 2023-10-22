<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class MorphToManyRelationStrategy implements Strategy
{
    use InteractsWithResourceFields;

    protected MorphToManyCollection $field;

    public function setField(MorphToManyCollection $field): void
    {
        $this->field = $field;
    }

    public function get(Model $model, string $attribute): array
    {
        $collection = collect();

        foreach ($this->field->resources as $relation => $resourceClass) {
            $collection = $collection->concat(
                $model->{$relation}()->get()->map(function (Model $model) use ($resourceClass) {
                    return new $resourceClass($model);
                })
            );
        }

        if ($this->field->sortBy) {
            $collection = $collection->sortBy(function (Resource $resource) {
                // @todo support custom "pivot" accessor
                return $resource->model()->pivot->getAttribute($this->field->sortBy);
            });
        }

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
            $requestCollectionByType = collect($request->all()[$requestAttribute] ?? [])
                ->map(fn (array $resource, $index) => array_merge($resource, [
                    'index' => $index
                ]))
                ->groupBy('type');

            foreach ($this->field->resources as $relation => $resourceClass) {
                $requestCollection = $requestCollectionByType[$resourceClass::uriKey()] ?? [];

                $syncPayload = [];

                foreach ($requestCollection as $requestResource) {
                    $collectionDictionary = $resourceClass::newModel()
                        ->newQuery()
                        ->findMany(collect($requestCollection)->map(fn (array $resource) => $requestResource['id'])->filter())
                        ->getDictionary();

                    if ($requestResource['mode'] === 'create') {
                        $modelForCreate = $this->createResourceModel($resourceClass, $requestResource['attributes']);

                        $syncPayload[$modelForCreate->getKey()] = $this->getPivotAttributes($requestResource);
                    } else if ($requestResource['mode'] === 'update' || $requestResource['mode'] === 'attach') {
                        $this->updateResourceModel($collectionDictionary[$requestResource['id']], $resourceClass, $requestResource['attributes']);

                        $syncPayload[$requestResource['id']] = $this->getPivotAttributes($requestResource);
                    }
                }

                $model->{$relation}()->sync($syncPayload);
            }
        };
    }

    protected function getPivotAttributes(array $resource): array
    {
        if ($this->field->sortBy) {
            return [$this->field->sortBy => $resource['index']];
        }

        return [];
    }
}
