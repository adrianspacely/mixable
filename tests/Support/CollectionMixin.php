<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixin;

/** @var \Illuminate\Support\Collection $this */

class CollectionMixin
{
    use Mixin;
    use TransposeCollectionMethod;
}
