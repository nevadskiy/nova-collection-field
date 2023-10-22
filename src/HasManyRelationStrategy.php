<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

class HasManyRelationStrategy implements Strategy
{
    use InteractsWithResourceFields;

    protected HasManyCollection $field;

    public function setField(HasManyCollection $field): void
    {
        $this->field = $field;
    }

    public function get(Model $model, string $attribute): array
    {
        $collection = $model->{$attribute}()->get();

        if ($this->field->sortBy) {
            $collection = $collection->sortBy(function (Model $model) {
                return $model->getAttribute($this->field->sortBy);
            });
        }

        $request = resolve(NovaRequest::class);

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
            $requestCollection = $request->all()[$requestAttribute] ?? [];

            $collectionDictionary = $model->{$attribute}()->get()->getDictionary();

            foreach ($this->getModelsToDelete($requestCollection, $collectionDictionary) as $modelForDelete) {
                $modelForDelete->delete();
            }

            foreach ($requestCollection as $index => $requestResource) {
                if ($requestResource['mode'] === 'create') {
                    $this->createResourceModel(
                        $this->field->resourceClass,
                        $requestResource['attributes'],
                        array_merge([
                            $model->{$attribute}()->getForeignKeyName() => $model->{$attribute}()->getParentKey()
                        ], $this->getSortAttribute($index))
                    );
                } else if ($requestResource['mode'] === 'update') {
                    $this->updateResourceModel(
                        $collectionDictionary[$requestResource['id']],
                        $this->field->resourceClass,
                        $requestResource['attributes'],
                        $this->getSortAttribute($index)
                    );
                }
            }
        };
    }

    protected function getSortAttribute(int $index): array
    {
        if ($this->field->sortBy) {
            return [$this->field->sortBy => $index];
        }

        return [];
    }

    protected function getModelsToDelete(array $requestCollection, array $collectionDictionary): array
    {
        $modelsForDelete = [];

        $requestCollectionDictionary = collect($requestCollection)
            ->filter(fn($resource) => $resource['id'])
            ->keyBy('id');

        foreach ($collectionDictionary as $collectionModel) {
            if (! isset($requestCollectionDictionary[$collectionModel->getKey()])) {
                $modelsForDelete[] = $collectionModel;
            }
        }

        return $modelsForDelete;
    }
}
