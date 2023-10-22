<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

class OneToManyRelationStrategy implements Strategy
{
    use InteractsWithResourceFields;

    protected OneToManyCollection $field;

    public function setField(OneToManyCollection $field): void
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
                    $this->createResourceModel(
                        $this->field->resourceClass,
                        $resource['attributes'],
                        array_merge($this->getSortAttribute($index), [
                            $model->{$attribute}()->getForeignKeyName() => $model->{$attribute}()->getParentKey()
                        ])
                    );
                } else if ($resource['mode'] === 'update') {
                    $this->updateResourceModel(
                        $relationModelsByKey[$resource['id']],
                        $this->field->resourceClass,
                        $resource['attributes'],
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
}
