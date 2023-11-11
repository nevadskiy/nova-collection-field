<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * @mixin Field
 */
trait HasValidationRules
{
    abstract protected function getRequestResourcesForValidation(NovaRequest $request): Collection;

    public function getCreationRules(NovaRequest $request): array
    {
        return array_merge_recursive(parent::getCreationRules($request), $this->getCollectionUpdateRules($request));
    }

    public function getUpdateRules(NovaRequest $request): array
    {
        return array_merge_recursive(parent::getUpdateRules($request), $this->getCollectionUpdateRules($request));
    }

    protected function getCollectionCreationRules($request): array
    {
        return $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                // @todo use NestedNovaRequest (same as client side) for specifying correct nested validation key.

                return FieldCollection::make($resource->fields($request))
                    ->mapWithKeys(function (Field $field) use ($request, $key) {
                        return [
                            $this->getValidationKeyByField($field, $key) => $field->getCreationRules($request)
                        ];
                    });
            })
            ->all();
    }

    protected function getCollectionUpdateRules($request): array
    {
        return $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                return FieldCollection::make($resource->fields($request))
                    ->mapWithKeys(function (Field $field) use ($request, $key) {
                        return [
                            $this->getValidationKeyByField($field, $key) => $field->getUpdateRules($request),
                        ];
                    });
            })
            ->all();
    }

    protected function getValidationKeyByField(Field $field, string $key): string
    {
        return "{$this->validationKey()}.{$key}.attributes.{$field->validationKey()}";
    }
}
