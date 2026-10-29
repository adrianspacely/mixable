<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Ref;
use AdrianBrown\Mixable\Tests\Support\Counter;

use function AdrianBrown\Mixable\ref;

beforeEach(function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function addPoints(int &$value)
        {
            $value = $value + $this->publicCount;

            return $this;
        }

        public function swap(&$left, &$right)
        {
            [$left, $right] = [$right, $left];
        }

        public function double(int $value)
        {
            return $value * 2;
        }
    })->mix();
});

test('a mixin macro updates an argument wrapped with `ref()`', function () {
    $value = 10;

    $result = (new Counter(5))->addPoints(ref($value));

    expect($value)->toBe(15);
    expect($result)->toBeInstanceOf(Counter::class);
});

test('a mixable macro updates an argument wrapped with `ref()`', function () {
    (new class () extends Counter {
        use Mixable;

        public function subtractPoints(int &$value)
        {
            $value = $value - $this->publicCount;
        }
    })->mix();
    $value = 10;

    (new Counter(5))->subtractPoints(ref($value));

    expect($value)->toBe(5);
});

test('a macro called statically updates an argument wrapped with `ref()`', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function increment(int &$value)
        {
            $value++;
        }
    })->mix();
    $value = 10;

    Counter::increment(ref($value));

    expect($value)->toBe(11);
});

test('more than one argument can be wrapped with `ref()`', function () {
    $left = 'a';
    $right = 'b';

    (new Counter())->swap(ref($left), ref($right));

    expect([$left, $right])->toBe(['b', 'a']);
});

test('named arguments can be wrapped with `ref()`', function () {
    $left = 'a';
    $right = 'b';

    (new Counter())->swap(right: ref($right), left: ref($left));

    expect([$left, $right])->toBe(['b', 'a']);
});

test('an argument wrapped with `ref()` is unwrapped for a parameter passed by value', function () {
    $value = 10;

    expect((new Counter())->double(ref($value)))->toBe(20);
    expect($value)->toBe(10);
});

test('an argument not wrapped with `ref()` is still passed by value', function () {
    $value = 10;

    (new Counter(5))->addPoints($value);

    expect($value)->toBe(10);
});

test('`ref()` and `new Ref()` hold a reference to the variable', function () {
    $value = 10;
    $ref = ref($value);
    $ref->value = 20;

    expect($value)->toBe(20);

    $other = 1;
    $otherRef = new Ref($other);
    $otherRef->value = 2;

    expect($other)->toBe(2);
});
