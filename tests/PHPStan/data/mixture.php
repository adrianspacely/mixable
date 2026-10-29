<?php

// Each line ending in `// type:` or `// error:` is checked by tests/PHPStanTest.php.

namespace AdrianBrown\Mixable\Tests\PHPStan\Data;

use AdrianBrown\Mixable\Mixture;
use AdrianBrown\Mixable\Tests\Support\SavingsWallet;
use AdrianBrown\Mixable\Tests\Support\Wallet;

use function PHPStan\dumpType;

$wallet = new Wallet();
$total = 10;

// `mixed()` keeps the type of the object it mixes.
dumpType(mixed($wallet)); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>
dumpType(new Mixture($wallet)); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>
dumpType(mixed($wallet)->mixedTarget()); // type: AdrianBrown\Mixable\Tests\Support\Wallet

// Mixin and mixable methods have their types, and keep the chain mixed when they return the object.
dumpType(mixed($wallet)->withdraw(10)); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>
dumpType(mixed($wallet)->double()); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>
dumpType(mixed($wallet)->label()); // type: string
dumpType(mixed(new SavingsWallet())->withdraw(10)); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\SavingsWallet>

// The object's own methods have their types too.
dumpType(mixed($wallet)->deposit(10)); // type: AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>
dumpType(mixed($wallet)->deposit(10)->withdraw(5)->getBalance()); // type: int

// Arguments are checked, including by-reference parameters.
mixed($wallet)->withdraw('ten'); // error: Parameter #1 $amount of method AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>::withdraw() expects int, string given.
mixed($wallet)->deposit('ten'); // error: Parameter #1 $amount of method AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>::deposit() expects int, string given.
mixed($wallet)->addBalanceTo(ref($total));
mixed($wallet)->depositInto(ref($total));
mixed($wallet)->addBalanceTo($total); // error: Parameter #1 $total of method AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>::addBalanceTo() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().
mixed($wallet)->depositInto($total); // error: Parameter #1 $total of method AdrianBrown\Mixable\Mixture<AdrianBrown\Mixable\Tests\Support\Wallet>::depositInto() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().

// Mixins of a class that isn't macroable are only available through `mixed()`.
$wallet->withdraw(10); // error: Call to an undefined method AdrianBrown\Mixable\Tests\Support\Wallet::withdraw().
