<?php

// Each line ending in `// type:` or `// error:` is checked by tests/PHPStanTest.php.

namespace AdrianSpacely\Mixable\Tests\PHPStan\Data;

use AdrianSpacely\Mixable\Ref;
use AdrianSpacely\Mixable\Tests\Support\Counter;

use function AdrianSpacely\Mixable\ref;
use function PHPStan\dumpType;

$value = 10;
$text = 'ten';

// `ref()` keeps the type of the variable it wraps.
dumpType(ref($value)); // type: AdrianSpacely\Mixable\Ref<int>
dumpType(new Ref($value)); // type: AdrianSpacely\Mixable\Ref<int>

// By-reference parameters of macros accept `ref()`.
(new Counter())->addPoints(ref($value));
(new Counter())->swap(ref($value), ref($value));
(new Counter())->swap(right: ref($value), left: ref($value));
Counter::addPoints(ref($value));

// Arguments for by-reference parameters of macros must be wrapped with `ref()`.
(new Counter())->addPoints($value); // error: Parameter #1 $value of method AdrianSpacely\Mixable\Tests\Support\Counter::addPoints() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().
(new Counter())->swap(ref($value), $value); // error: Parameter #2 $right of method AdrianSpacely\Mixable\Tests\Support\Counter::swap() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().
(new Counter())->swap(right: $value, left: ref($value)); // error: Parameter #2 $right of method AdrianSpacely\Mixable\Tests\Support\Counter::swap() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().
Counter::addPoints($value); // error: Parameter #1 $value of method AdrianSpacely\Mixable\Tests\Support\Counter::addPoints() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().

// The wrapped variable is checked against the parameter's type.
(new Counter())->addPoints(ref($text)); // error: Parameter #1 $value of method AdrianSpacely\Mixable\Tests\Support\Counter::addPoints() expects AdrianSpacely\Mixable\Ref<int>|int, AdrianSpacely\Mixable\Ref<string> given.
