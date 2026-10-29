<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixer;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Mixture;
use AdrianBrown\Mixable\Tests\Support\CollectionMixin;
use AdrianBrown\Mixable\Tests\Support\HasBalance;
use AdrianBrown\Mixable\Tests\Support\SavingsWallet;
use AdrianBrown\Mixable\Tests\Support\Wallet;
use AdrianBrown\Mixable\Tests\Support\WalletMixable;
use AdrianBrown\Mixable\Tests\Support\WalletMixin;
use BadMethodCallException;
use Illuminate\Support\Collection;

use function AdrianBrown\Mixable\mixed;
use function AdrianBrown\Mixable\ref;

it('records mixins for a class that is not macroable', function () {
    WalletMixin::mix();
    WalletMixable::mix();

    expect(Mixer::mixins()[Wallet::class])->toBe([
        'withdraw' => WalletMixin::class,
        'label' => WalletMixin::class,
        'addBalanceTo' => WalletMixin::class,
        'double' => WalletMixable::class,
    ]);
});

it('calls mixin methods on the object through `mixed()`', function () {
    WalletMixin::mix();
    $wallet = new Wallet(100);

    $mixture = mixed($wallet)->withdraw(30);

    expect($mixture)->toBeInstanceOf(Mixture::class);
    expect($mixture->label())->toBe('Grace: 70');
    expect($wallet->getBalance())->toBe(70);
});

it('forwards other calls to the object, and keeps the chain mixed when the object returns itself', function () {
    WalletMixin::mix();
    $wallet = new Wallet(100);

    $balance = mixed($wallet)->deposit(10)->withdraw(30)->getBalance();

    expect($balance)->toBe(80);
});

it('gives back the object it mixes', function () {
    $wallet = new Wallet();

    expect(mixed($wallet)->mixedTarget())->toBe($wallet);
    expect((new Mixture($wallet))->mixedTarget())->toBe($wallet);
});

it('gets and sets public properties on the object', function () {
    $wallet = new Wallet();
    $mixture = mixed($wallet);

    $mixture->owner = 'Ada';

    expect($wallet->owner)->toBe('Ada');
    expect($mixture->owner)->toBe('Ada');
    expect(isset($mixture->owner))->toBe(true);
    expect(isset($mixture->missing))->toBe(false);
});

it('uses mixins registered on a parent class', function () {
    WalletMixin::mix();

    expect(mixed(new SavingsWallet(100))->withdraw(30)->getBalance())->toBe(70);
});

it('uses mixins registered on an interface', function () {
    (new class () {
        use Mixin;

        public function doubleBalance(): int
        {
            return $this->getBalance() * 2;
        }
    })->mix(HasBalance::class);

    expect(mixed(new Wallet(21))->doubleBalance())->toBe(42);
});

it('uses mixables for a parent class that is not macroable', function () {
    WalletMixable::mix();
    $wallet = new Wallet(21);

    $mixture = mixed($wallet)->double();

    expect($mixture)->toBeInstanceOf(Mixture::class);
    expect($mixture->mixedTarget())->toBe($wallet);
    expect($wallet->getBalance())->toBe(42);
});

it('passes arguments wrapped with `ref()` by reference', function () {
    WalletMixin::mix();
    $mixture = mixed(new Wallet(5));
    $total = 10;

    $mixture->addBalanceTo(ref($total));
    $mixture->depositInto(ref($total));

    expect($total)->toBe(20);
});

it('uses mixins on a macroable object', function () {
    CollectionMixin::mix(Collection::class);

    $transposed = mixed(collect([[1, 2], [3, 4]]))->transpose();

    expect($transposed)->toBeInstanceOf(Collection::class);
    expect($transposed->toArray())->toBe([[1, 3], [2, 4]]);
});

it('throws for a method the object and its mixins do not have', function () {
    WalletMixin::mix();

    expect(fn () => mixed(new Wallet())->missing())
        ->toThrow(BadMethodCallException::class, 'Call to undefined method ' . Wallet::class . '::missing()');
});

it('does not use mixins registered on another class', function () {
    WalletMixin::mix();

    expect(fn () => mixed(collect())->withdraw(1))
        ->toThrow(BadMethodCallException::class);
});
