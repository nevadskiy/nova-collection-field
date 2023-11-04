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

    public function getRules(NovaRequest $request): array
    {
        return [];
    }

    public function getCreationRules(NovaRequest $request): array
    {
        return [];

        if ($request->method() === 'GET') {
            return [];
        }

        return $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                return FieldCollection::make($resource->fields($request))
                    ->mapWithKeys(function (Field $field) use ($request, $key) {
                        return [
                            $this->getValidationKeyByField($field, $key) => $field->rules
                        ];
                    });
            })
            ->all();
    }

    public function getUpdateRules(NovaRequest $request): array
    {
        return [];

        if ($request->method() === 'GET') {
            return [];
        }

        return $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                return FieldCollection::make($resource->fields($request))
                    ->mapWithKeys(function (Field $field) use ($request, $key) {
                        return [
                            $this->getValidationKeyByField($field, $key) => $field->rules
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
