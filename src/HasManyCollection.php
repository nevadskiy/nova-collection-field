<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class HasManyCollection extends Field
{
    use HasValidationRules;
    use Collapsable;

    public $component = 'collection-field';

    public string $resourceClass;

    public ?string $sortBy = null;

    public Strategy $strategy;

    public function __construct(string $name, string $relation, string $resourceClass)
    {
        parent::__construct($name, $relation);

        $this->resourceClass = $resourceClass;

        $this->useStrategy(new HasManyRelationStrategy());

        $this->showOnIndex = false;
        $this->showOnDetail = false;
    }

    public function sortBy(?string $sortBy): static
    {
        $this->sortBy = $sortBy;

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

    protected function getRequestResourcesForValidation(NovaRequest $request, string $viaAttribute = ''): Collection
    {
        return collect([
            '*' => new $this->resourceClass($this->resourceClass::newModel())
        ]);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'resource' => $this->serializeResource(),
            'sortable' => ! is_null($this->sortBy),
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
        ]);
    }

    protected function serializeResource(): array
    {
        $request = app(NovaRequest::class);

        $resource = new $this->resourceClass($this->resourceClass::newModel());

        return [
            'type' => $resource::uriKey(),
            'label' => $resource::label(),
            'singularLabel' => $resource::singularLabel(),
            'fields' => $resource->creationFields($request)->values()->all(),
        ];
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
