<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixable;
use Illuminate\Support\Collection as BaseCollection;

/** @var \Illuminate\Support\Collection $this */

class CollectionMixable extends BaseCollection
{
    use Mixable;
    use TransposeCollectionMethod;

    protected static function fromMacroable(BaseCollection $parent): self
    {
        return self::make($parent->all());
    }

    public function toMacroable(): BaseCollection
    {
        return BaseCollection::make($this->items);
    }
}
