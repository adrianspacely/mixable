<?php

namespace AdrianSpacely\Mixable\Tests;

use AdrianSpacely\Mixable\Mixin;
use AdrianSpacely\Mixable\Tests\Support\TestMe;

test('problem: static::class gives the mixin class name', function () {
    $mixin = new class () {
        use Mixin;

        public function mixinGetClassName()
        {
            return static::class;
        }
    };
    $mixin->mix(TestMe::class);
    expect(TestMe::hasMacro('mixinGetClassName'))->toBe(true);
    $testMe = TestMe::new();

    tap($testMe->mixinGetClassName(), function ($result) use ($mixin, $testMe) {
        expect($result)->toBe(get_class($mixin));
        expect($result)->not->toBe(TestMe::class);
    });
});

test('solution: static::class gives the macroable class name with these solutions', function () {
    ($mixin = new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function inScopeSolution()
        {
            return $this->inScope(fn () => static::class);
        }

        public function noStaticKeywordSolution()
        {
            return TestMe::class;
        }

        public function getClassOnMacroableInstanceSolution()
        {
            return get_class($this->macroableInstance);
        }
    })->mix();

    collect([
        'inScopeSolution',
        'noStaticKeywordSolution',
        'getClassOnMacroableInstanceSolution',
    ])->each(fn ($method) => tap(TestMe::new()->{$method}(), function ($result) use ($mixin) {
        expect($result)->toBe(TestMe::class);
        expect($result)->not->toBe(get_class($mixin));
    }));
});
