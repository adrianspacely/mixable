<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use Countable;
use LengthException;

trait TransposeCollectionMethod
{
    public function transpose()
    {
        if ($this->isEmpty()) {
            return static::make();
        }

        $items = $this->items;
        $firstItem = $this->first();
        $expectedLength = is_array($firstItem) || $firstItem instanceof Countable ? count($firstItem) : 0;

        array_walk($items, function ($row) use ($expectedLength) {
            if ((is_array($row) || $row instanceof Countable) && count($row) !== $expectedLength) {
                throw new LengthException("Element's length must be equal.");
            }
        });

        $items = array_map(function (...$items) {
            return static::make($items);
        }, ...array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, array_values($items)));

        return static::make($items);
    }
}
