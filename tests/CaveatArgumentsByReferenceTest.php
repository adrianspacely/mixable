<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Tests\Support\Counter;
use ArrayObject;

use function AdrianBrown\Mixable\ref;

test('problem: a mixin macro receives arguments by value, because PHP passes `__call()` arguments by value', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function addPoints(int &$value)
        {
            $value = $value + $this->publicCount;
        }
    })->mix();
    $value = 10;

    (new Counter(5))->addPoints($value);

    expect($value)->toBe(10);
});

test('problem: a mixable macro receives arguments by value, because PHP passes `__call()` arguments by value', function () {
    (new class () extends Counter {
        use Mixable;

        public function addPoints(int &$value)
        {
            $value = $value + $this->publicCount;
        }
    })->mix();
    $value = 10;

    (new Counter(5))->addPoints($value);

    expect($value)->toBe(10);
});

test('solution: wrap the argument with `ref()`', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function addPoints(int &$value)
        {
            $value = $value + $this->publicCount;
        }
    })->mix();
    $value = 10;

    (new Counter(5))->addPoints(ref($value));

    expect($value)->toBe(15);
});

test('solution: return the value instead', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function addPoints(int $value)
        {
            return $value + $this->publicCount;
        }
    })->mix();
    $value = 10;

    $value = (new Counter(5))->addPoints($value);

    expect($value)->toBe(15);
});

test('solution: pass an object, because objects are passed by handle', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function addPoints(ArrayObject $score)
        {
            $score['value'] = $score['value'] + $this->publicCount;
        }
    })->mix();
    $score = new ArrayObject(['value' => 10]);

    (new Counter(5))->addPoints($score);

    expect($score['value'])->toBe(15);
});

test('solution: call the method on the mixable subclass, because it is a real method there', function () {
    $mixable = new class (5) extends Counter {
        use Mixable;

        public function addPoints(int &$value)
        {
            $value = $value + $this->publicCount;
        }
    };
    $value = 10;

    $mixable->addPoints($value);

    expect($value)->toBe(15);
});
