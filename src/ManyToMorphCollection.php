<?php

namespace Nevadskiy\Nova\Collection;

class ManyToMorphCollection extends MorphToManyCollection
{
    public function __construct(string $name, string $attribute = null)
    {
        parent::__construct($name, $attribute);

        $this->useStrategy(new ManyToMorphRelationStrategy());
    }
}
