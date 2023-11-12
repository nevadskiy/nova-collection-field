<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class MorphToManyCollection extends Field
{
    use Collapsable;
    use HasValidationRules;

    public $component = 'morph-collection-field';

    public array $resources = [];

    public ?string $sortBy = null;

    public bool $attachable = false;

    public bool $skipIfNoChanges = false;

    public Strategy $strategy;

    public function __construct(string $name, string $attribute = null)
    {
        parent::__construct($name, $attribute);

        $this->useStrategy(new MorphToManyRelationStrategy());

        $this->showOnIndex = false;
        $this->showOnDetail = false;
    }

    public function resources(array $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function sortBy(?string $sortBy): static
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function attachable(bool $attachable = true): static
    {
        $this->attachable = $attachable;

        return $this;
    }

    public function skipIfNoChanges(bool $skipIfNoChanges = true): static
    {
        $this->skipIfNoChanges = $skipIfNoChanges;

        return $this;
    }

    protected function resolveAttribute($resource, $attribute)
    {
        return $this->strategy->get($resource, $attribute);
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        return $this->strategy->set($request, $requestAttribute, $model, $attribute);
    }

    protected function getRequestResourcesForValidation(NovaRequest $request): Collection
    {
        $resourcesByType = [];

        foreach ($this->resources as $resourceClass) {
            $resourcesByType[$resourceClass::uriKey()] = new $resourceClass($resourceClass::newModel());
        }

        return collect($request->input("{$this->getNestedValidationKey($request)}{$this->validationKey()}"))
            ->filter(function (array $requestResource) {
                return isset($requestResource['attributes']);
            })
            ->map(function (array $requestResource) use ($resourcesByType) {
                return $resourcesByType[$requestResource['type']];
            });
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'resources' => $this->serializeResources(),
            'sortable' => ! is_null($this->sortBy),
            'attachable' => $this->attachable,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'skipIfNoChanges' => $this->skipIfNoChanges,
        ]);
    }

    protected function serializeResources(): array
    {
        $request = app(NovaRequest::class);

        $resources = [];

        foreach ($this->resources as $resourceClass) {
            $resources[] = $this->serializeResource(new $resourceClass($resourceClass::newModel()), $request);
        }

        return $resources;
    }

    protected function serializeResource(Resource $resource, NovaRequest $request): array
    {
        return [
            'type' => $resource::uriKey(),
            'label' => $resource::label(),
            'singularLabel' => $resource::singularLabel(),
            'fields' => $resource->creationFields($request)->values()->all(),
        ];
    }

    /**
     * @todo handle authorization
     */
    public function paginateAttachableResources(NovaRequest $request, string $resourceName, int $perPage = 25)
    {
        $resourceClass = collect($this->resources)->firstOrFail(function ($resourceClass) use ($resourceName) {
            return $resourceClass::uriKey() === $resourceName;
        });

        return $resourceClass::buildIndexQuery($request, $resourceClass::newModel()->newQuery(), $request->query('search'))
            ->paginate($perPage)
            ->through(function ($model) use ($resourceClass, $request) {
                $resource = new $resourceClass($model);

                return [
                    'id' => $resource->getKey(),
                    'type' => $resource::uriKey(),
                    'label' => $resource::label(),
                    'singularLabel' => $resource::singularLabel(),
                    'fields' => $resource->updateFields($request)->values()->all(),
                    'title' => $resource->title(),
                    'subtitle' => $resource->subtitle(),
                    'avatar' => $resource->resolveAvatarUrl($request),
                ];
            });
    }

    public function useStrategy(Strategy $strategy): static
    {
        $this->strategy = $strategy;

        if (method_exists($strategy, 'setField')) {
            $strategy->setField($this);
        }

        return $this;
    }
}
