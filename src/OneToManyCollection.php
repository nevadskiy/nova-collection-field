<?php

namespace Nevadskiy\Nova\Collection;

use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class OneToManyCollection extends Field
{
    use Collapsable;

    public $component = 'one-to-many-collection-field';

    public string $resourceClass;

    public function __construct(string $name, string $relation, string $resourceClass)
    {
        parent::__construct($name, $relation);

        $this->resourceClass = $resourceClass;
        $this->showOnIndex = false;
        $this->showOnDetail = false;
    }

    public function jsonSerialize(): array
    {
        return array_merge([
            'resource' => $this->serializeResource(),
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
        ], parent::jsonSerialize());
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
}
