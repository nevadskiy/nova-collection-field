<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * @mixin Field
 */
trait HasValidationRules
{
    abstract protected function getRequestResourcesForValidation(NovaRequest $request, string $viaAttribute = ''): Collection;

    public function getCreationRules(NovaRequest $request): array
    {
        return $this->getNestedCreationRules($request);
    }

    protected function getNestedCreationRules(NovaRequest $request, string $viaAttribute = ''): array
    {
        return $this->getRequestResourcesForValidation($request, $viaAttribute)
            ->flatMap(function (Resource $resource, string $key) use ($request, $viaAttribute) {
                $rules = [];

                $viaAttribute = $this->getNestedValidationKey($viaAttribute, $key);

                foreach ($resource->fields($request) as $field) {
                    if (method_exists($field, 'getNestedCreationRules')) {
                        foreach ($field->getNestedCreationRules($request, $viaAttribute) as $fieldAttribute => $fieldRules) {
                            $rules[$fieldAttribute] = $fieldRules;
                        }
                    } else {
                        foreach ($field->getCreationRules($request) as $fieldAttribute => $fieldRules) {
                            $rules[$viaAttribute . $fieldAttribute] = $fieldRules;
                        }
                    }
                }

                return $rules;
            })
            ->all();
    }

    public function getUpdateRules(NovaRequest $request): array
    {
        return $this->getNestedUpdateRules($request);
    }

    public function getNestedUpdateRules(NovaRequest $request, string $viaAttribute = ''): array
    {
        return $this->getRequestResourcesForValidation($request, $viaAttribute)
            ->flatMap(function (Resource $resource, string $key) use ($request, $viaAttribute) {
                $rules = [];

                $viaAttribute = $this->getNestedValidationKey($viaAttribute, $key);

                foreach ($resource->fields($request) as $field) {
                    if (method_exists($field, 'getNestedUpdateRules')) {
                        foreach ($field->getNestedUpdateRules($request, $viaAttribute) as $fieldAttribute => $fieldRules) {
                            $rules[$fieldAttribute] = $fieldRules;
                        }
                    } else {
                        foreach ($field->getUpdateRules($request) as $fieldAttribute => $fieldRules) {
                            $rules[$viaAttribute . $fieldAttribute] = $fieldRules;
                        }
                    }
                }

                return $rules;
            })
            ->all();
    }

    protected function getNestedValidationKey(string $viaAttribute, string $key): string
    {
        return "{$viaAttribute}{$this->validationKey()}.{$key}.attributes.";
    }

    public function isRequired(NovaRequest $request): bool
    {
        return false;
    }
}
