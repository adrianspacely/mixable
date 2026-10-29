<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixer;
use AdrianBrown\Mixable\Tests\Support\CollectionMixable;
use AdrianBrown\Mixable\Tests\Support\Counter;
use AdrianBrown\Mixable\Tests\Support\CounterMixable;
use AdrianBrown\Mixable\Tests\Support\Ticket;
use Illuminate\Support\Collection;
use ReflectionClass;

it('works', function () {
    CollectionMixable::mix();

    $parentCollection = Collection::make([
        [0, 1],
        [0, 1],
        [0, 1],
    ]);
    $extendCollection = CollectionMixable::make([
        [0, 1],
        [0, 1],
        [0, 1],
    ]);

    expect($parentCollection)->toBeInstanceOf(Collection::class);
    expect($extendCollection)->toBeInstanceOf(CollectionMixable::class);

    $parentCollectionTransposed = $parentCollection->transpose();
    $extendCollectionTransposed = $extendCollection->transpose();

    expect($parentCollectionTransposed)->toBeInstanceOf(Collection::class);
    expect($extendCollectionTransposed)->toBeInstanceOf(CollectionMixable::class);

    expect($parentCollectionTransposed->toArray())->toBe([
        [0, 0, 0],
        [1, 1, 1],
    ]);
    expect($extendCollectionTransposed->toArray())->toBe([
        [0, 0, 0],
        [1, 1, 1],
    ]);
});

it('does not add the macros to the class it extends if `mix()` is not called', function () {
    expect(Collection::hasMacro('transpose'))->toBe(false);

    CollectionMixable::mix();

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('records which mixable each macro came from', function () {
    CounterMixable::mix();

    expect(Mixer::mixins()[Counter::class])->toMatchArray([
        'increment' => CounterMixable::class,
        'whoami' => CounterMixable::class,
    ]);
    expect(Mixer::mixins()[Counter::class])->not->toHaveKey('bootMixable');
    expect(Counter::hasMacro('bootMixable'))->toBe(false);
});

it("creates the subclass instance using the macroable parent class instance's state", function () {
    ($mixable = new class () extends Collection {
        use Mixable;

        protected static function newMixableInstance($parent): self
        {
            // The parent comes in.
            test()->expect($parent)->toBeInstanceOf(Collection::class);
            test()->expect($parent->toArray())->toBe(['a', 'b', 'c']);

            // New mixable instance.
            return self::make([1, 2, 3]);
        }

        public function newMacroableInstance(): Collection
        {
            // New parent instance out from mixable values.
            return Collection::make($this->items);
        }

        public function example()
        {
            expect($this->toArray())->toBe([1, 2, 3]);

            return $this;
        }
    })->mix();

    // macroable in / macroable out
    $result = collect(['a', 'b', 'c'])->example()->push('d');

    expect($result->toArray())->toBe([1, 2, 3, 'd']);
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->not->toBeInstanceOf(get_class($mixable));
});

it("returns the macroable instance with it's state updated to be the same as the mixable subclass state", function () {
    CounterMixable::mix();
    $counter = new Counter(5);

    $result = $counter->increment();

    expect($result)->toBe($counter);
    expect($counter->counts())->toBe([6, 6, 6, 1]);
});

test('the mixable interacts with itself', function () {
    CounterMixable::mix();
    $counter = new Counter();

    $result = $counter->incrementTwice();

    expect($result)->toBe($counter);
    expect($counter->counts())->toBe([2, 2, 2, 2]);
});

test("the parent macroable instance's state is not affected within the mixable subclass", function () {
    CounterMixable::mix();
    $counter = new Counter(5);

    $countsDuringMacro = $counter->incrementAndCountMacroable();

    expect($countsDuringMacro)->toBe([5, 5, 5, 0]);
    expect($counter->counts())->toBe([6, 6, 6, 1]);
});

test('can pull state from the macroable to the mixable', function () {
    $counter = new Counter(5);
    $counter->bump();
    $mixable = (new ReflectionClass(CounterMixable::class))->newInstanceWithoutConstructor();
    $mixable->booted = 'mixable_only';

    Mixer::invade($mixable, function () use ($counter) {
        $this->macroableInstance = $counter;
        $this->pullMacroableState();
    });

    expect($mixable->counts())->toBe([6, 6, 6, 1]);
    expect($mixable->constructed)->toBe(true);
    expect($mixable->booted)->toBe('mixable_only');
});

test('can push state from the mixable to the macroable', function () {
    $counter = new Counter();
    $mixable = new CounterMixable(5);
    $mixable->bump();

    Mixer::invade($mixable, function () use ($counter) {
        $this->macroableInstance = $counter;
        $this->pushMacroableState();
    });

    expect($counter->counts())->toBe([6, 6, 6, 1]);
    expect(property_exists($counter, 'booted'))->toBe(false);
});

test('cannot pull state from the macroable to the mixable if the macroable is not set', function () {
    $mixable = new CounterMixable(5);

    Mixer::invade($mixable, fn () => $this->pullMacroableState());

    expect($mixable->counts())->toBe([5, 5, 5, 0]);
});

test('cannot push state from the mixable to the macroable if the macroable is not set', function () {
    $mixable = new CounterMixable(5);

    Mixer::invade($mixable, fn () => $this->pushMacroableState());

    expect($mixable->counts())->toBe([5, 5, 5, 0]);
});

test('returning a different mixable instance will not result in a swap of the return value to the macroable instance', function () {
    CounterMixable::mix();
    $counter = new Counter();

    $result = $counter->incrementAndCopy();

    expect($result)->toBeInstanceOf(CounterMixable::class);
    expect($result->counts())->toBe([1, 1, 1, 1]);
});

test('returning some primative value will not result in a swap of the return value to the macroable instance', function () {
    CounterMixable::mix();
    $counter = new Counter(5);

    expect($counter->incrementAndCount())->toBe(6);
});

test('returning some primative value will still result in the macroable state being synced with the mixable state', function () {
    CounterMixable::mix();
    $counter = new Counter(5);

    $counter->incrementAndCount();

    expect($counter->counts())->toBe([6, 6, 6, 1]);
});

test("`inScope` sets the macroable parent instance's state going in, and sets the mixable subclass instance's state going out", function () {
    CounterMixable::mix();
    $counter = new Counter();

    $countsInMixable = $counter->incrementInAndOutOfScope();

    expect($countsInMixable)->toBe([2, 2, 2, 2]);
    expect($counter->counts())->toBe([2, 2, 2, 2]);
});

test('`inScope` uses the macroable parent class scope when called from a macro', function () {
    CounterMixable::mix();

    expect((new Counter())->whoami())->toBe(Counter::class);
    expect((new CounterMixable())->whoami())->toBe(CounterMixable::class);
});

test('can implement a `bootMixable()` function to do something to the mixable after it is instantiated', function () {
    CounterMixable::mix();

    expect((new Counter())->wasBooted())->toBe(true);
    expect((new CounterMixable())->wasBooted())->toBe(false);
});

test('can call a macro statically', function () {
    CounterMixable::mix();

    $result = Counter::increment();

    expect($result)->toBeInstanceOf(CounterMixable::class);
    expect($result->counts())->toBe([1, 1, 1, 1]);
    expect(Counter::whoami())->toBe(CounterMixable::class);
});

test('copies readonly properties into the mixable', function () {
    (new class ('unused') extends Ticket {
        use Mixable;

        public function close()
        {
            $this->status = "closed {$this->id}";

            return $this;
        }
    })->mix();
    $ticket = new Ticket('T-1');

    $result = $ticket->close();

    expect($result)->toBe($ticket);
    expect($ticket->status)->toBe('closed T-1');
});

test('only copies the state of classes the macroable instance shares with the mixable', function () {
    (new class () extends CounterMixable {
        public function incrementGrandchild()
        {
            return $this->increment();
        }
    })->mix(Counter::class);
    $counter = new Counter(5);

    $result = $counter->incrementGrandchild();

    expect($result)->toBe($counter);
    expect($counter->counts())->toBe([6, 6, 6, 1]);
    expect(property_exists($counter, 'booted'))->toBe(false);
});
