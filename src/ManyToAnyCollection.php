<?php

namespace Nevadskiy\Nova\Collection;

use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class ManyToAnyCollection extends Field
{
    use Collapsable;

    public $component = 'many-to-any-collection-field';

    public array $resources = [];

    public string|null $sortByPivot = null;

    public bool $attachable = false;

    public Strategy $strategy;

    public function __construct(string $name, string $attribute = null)
    {
        parent::__construct($name, $attribute);

        $this->showOnIndex = false;
        $this->showOnDetail = false;

        $this->useStrategy(new ManyToAnyRelationStrategy());
    }

    public function resources(array $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function sortByPivot(string $pivot): static
    {
        $this->sortByPivot = $pivot;

        return $this;
    }

    public function attachable(bool $attachable = true): static
    {
        $this->attachable = $attachable;

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

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'resources' => $this->serializeResources(),
            'sortBy' => $this->sortByPivot,
            'attachable' => $this->attachable,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
        ]);
    }

    protected function serializeResources(): array
    {
        $request = app(NovaRequest::class);

        $resources = [];

        foreach ($this->resources as $resourceClass => $relation) {
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
        $resourceClass = collect($this->resources)->keys()->firstOrFail(function ($resourceClass) use ($resourceName) {
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
