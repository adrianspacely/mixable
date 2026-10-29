<?php

// Each line ending in `// type:` or `// error:` is checked by tests/PHPStanTest.php.

namespace AdrianSpacely\Mixable\Tests\PHPStan\Data;

use AdrianSpacely\Mixable\Tests\Support\Counter;
use AdrianSpacely\Mixable\Tests\Support\SpecialCounter;

use function PHPStan\dumpType;

// Mixin macros have the mixin method's types, with `$this` and `static` as the macroable.
dumpType((new Counter())->add(1)); // type: AdrianSpacely\Mixable\Tests\Support\Counter
dumpType((new Counter())->reset()); // type: AdrianSpacely\Mixable\Tests\Support\Counter
dumpType((new Counter())->label()); // type: string
dumpType((new SpecialCounter())->add(1)); // type: AdrianSpacely\Mixable\Tests\Support\SpecialCounter

// Mixable macros have the mixable method's types, with `self` as the macroable.
dumpType((new Counter())->double()); // type: AdrianSpacely\Mixable\Tests\Support\Counter
dumpType((new Counter())->history()); // type: list<int>

// Arguments are checked against the mixin method's parameters.
(new Counter())->add('one'); // error: Parameter #1 $amount of method AdrianSpacely\Mixable\Tests\Support\Counter::add() expects int, string given.

// Methods that were not mixed in are still undefined.
(new Counter())->subtract(1); // error: Call to an undefined method AdrianSpacely\Mixable\Tests\Support\Counter::subtract().
