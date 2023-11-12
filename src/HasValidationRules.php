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
    abstract protected function getRequestResourcesForValidation(NovaRequest $request): Collection;

    public function getCreationRules(NovaRequest $request): array
    {
        return $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                return $this->withNestedValidationKey($request, $key, function () use ($request, $resource) {
                    $rules = [];

                    foreach ($resource->fields($request) as $field) {
                        if ($field instanceof HasNestedValidationRules) {
                            foreach ($field->getCreationRules($request) as $nestedField => $nestedFieldRules) {
                                $rules[$nestedField] = $nestedFieldRules;
                            }
                        } else {
                            $nestedValidationKey = $this->getNestedValidationKey($request);

                            foreach ($field->getCreationRules($request) as $fieldAttribute => $fieldAttributeRules) {
                                $rules[$nestedValidationKey . $fieldAttribute] = $fieldAttributeRules;
                            }
                        }
                    }

                    return $rules;
                });
            })
            ->all();
    }

    public function getUpdateRules(NovaRequest $request): array
    {
        $rules = $this->getRequestResourcesForValidation($request)
            ->flatMap(function (Resource $resource, string $key) use ($request) {
                return $this->withNestedValidationKey($request, $key, function () use ($request, $resource) {
                    $rules = [];

                    foreach ($resource->fields($request) as $field) {
                        $nestedValidationKey = $this->getNestedValidationKey($request);

                        foreach ($field->getUpdateRules($request) as $fieldAttribute => $fieldAttributeRules) {
                            $rules[$field instanceof MorphToManyCollection ? $fieldAttribute : $nestedValidationKey . $fieldAttribute] = $fieldAttributeRules;
                        }
                    }

                    return $rules;
                });
            })
            ->all();

        app('log')->info('rules', $rules);

        return $rules;
    }

    protected function withNestedValidationKey(NovaRequest $request, string $key, callable $callback): mixed
    {
        $original = $request->attributes->get('collection.validation.namespace', '');

        $request->attributes->set('collection.validation.namespace', $original . "{$this->attribute}.{$key}.attributes.");

        $result = $callback();

        $request->attributes->set('collection.validation.namespace', $original);

        return $result;
    }

    protected function getNestedValidationKey(NovaRequest $request): string
    {
        return $request->attributes->get('collection.validation.namespace');
    }
}
