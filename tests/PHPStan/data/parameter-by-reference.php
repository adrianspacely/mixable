<?php

// Each line ending in `// error:` is checked by tests/PHPStanTest.php.

namespace AdrianBrown\Mixable\Tests\PHPStan\Data;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Tests\Support\Counter;

class PointsMixin
{
    use Mixin;

    public function addPoints(int &$value): void // error: Parameter #1 $value of Mixin method AdrianBrown\Mixable\Tests\PHPStan\Data\PointsMixin::addPoints() is passed by reference, but macros receive arguments by value.
    {
        $value++;
    }

    public function addPointsByValue(int $value): int
    {
        return $value + 1;
    }

    public static function staticByReference(int &$value): void
    {
        $value++;
    }

    protected function protectedByReference(int &$value): void
    {
        $value++;
    }
}

class PointsMixable extends Counter
{
    use Mixable;

    public function addPoints(int $start, int &$value): void // error: Parameter #2 $value of Mixable method AdrianBrown\Mixable\Tests\PHPStan\Data\PointsMixable::addPoints() is passed by reference, but macros receive arguments by value.
    {
        $value = $start + $value;
    }
}

class NotAMixin
{
    public function addPoints(int &$value): void
    {
        $value++;
    }
}
