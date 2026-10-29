<?php

namespace AdrianSpacely\Mixable\Tests;

use AdrianSpacely\Mixable\Mixin;
use Illuminate\Support\Collection;

test('problem: passing attributes by reference will cause PHP to warn about indirect attribute changes via magic methods', function () {
    (new class () {
        use Mixin;

        public string $macroable = Collection::class;

        public function mixinDoingSomethingByReference()
        {
            array_walk($this->items, function (&$item) {
                $item = $item * 2;
            });

            return $this;
        }
    })->mix();
    expect(Collection::hasMacro('mixinDoingSomethingByReference'))->toBe(true);

    $result = collect([1, 2, 3])->mixinDoingSomethingByReference(); // Will throw an exception
})->throws('Indirect modification of overloaded property class@anonymous::$items has no effect');

test('solution: passing by reference works with these solutions', function () {
    (new class () {
        use Mixin;

        public string $macroable = Collection::class;

        public function inScopeSolution()
        {
            $this->inScope(fn () => array_walk($this->items, function (&$item) {
                $item = $item * 2;
            }));

            return $this;
        }

        public function copyValueSolution()
        {
            $items = $this->items;
            array_walk($items, function (&$item) {
                $item = $item * 2;
            });
            $this->items = $items;

            return $this;
        }
    })->mix();

    collect([
        'inScopeSolution',
        'copyValueSolution',
    ])->each(fn ($method) => tap(collect([1, 2, 3])->{$method}(), function ($result) {
        expect($result->toArray())->toBe([2, 4, 6]);
    }));
});
