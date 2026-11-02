---
name: mixable-development
description: Write, change, register, and test Mixins and Mixables from adrianspacely/mixable, pass by-reference arguments to macros with ref(), and add methods to classes that aren't Macroable with mixed().
---

# Mixable Development

## When to use this skill

Use this skill when you:

- add methods to a Laravel `Macroable` class, like `Collection`, `Builder`, `Request`, or `Str`;
- write or change a class that uses the `Mixin` or `Mixable` trait;
- add methods to a class that isn't `Macroable`, like `Illuminate\Bus\PendingBatch`;
- call a macro with a by-reference parameter;
- test code that registers macros.

## Choosing an approach

| You want to | Use |
| --- | --- |
| Add methods to one or more `Macroable` classes you don't own | A `Mixin` on a plain class |
| Share methods between a `Macroable` parent and your subclass of it, like a dedicated collection or query builder | A `Mixable` on the subclass |
| Add methods to a class that isn't `Macroable` | A `Mixin` (or `Mixable`), called through `mixed($object)` |

## Mixins

A Mixin is a plain class with the `Mixin` trait. Its public, non-static methods become macros. Inside them, `$this` is a decorator: property reads and writes and method calls go to the object the macro was called on, including protected and private members declared on any of its classes.

```php
namespace App\Mixins;

use AdrianSpacely\Mixable\Mixin;
use Illuminate\Support\Collection;

class LoggerMixin
{
    use Mixin;

    /** @var list<class-string> The classes `mix()` registers this Mixin on. */
    public array $macroables = [Collection::class];

    public function info(array $context = []): static
    {
        logger()->info(json_encode($this->toArray()), $context);

        return $this;
    }
}

// In a service provider:
LoggerMixin::mix();                                 // Registers on $macroable / $macroables.
LoggerMixin::mix(\Illuminate\Http\Request::class); // Registers on Request as well.
```

- Returning `$this` returns the object the macro was called on, not the Mixin.
- `$this` is not the object itself, and `static::class` is the Mixin. When you need the real object, or its class scope, use `$this->inScope(fn () => ...)`. Inside the callback, `$this` and `static` are the object.
- Passing a property by reference through `$this` raises "Indirect modification of overloaded property". Copy it to a local variable, or work inside `inScope()`.
- `mix()` throws if the Mixin is a subclass of the class it mixes into. Use a Mixable for that.
- `mix()` throws if a Mixin method has the same name as a method of the class it mixes into, of any visibility. Give the Mixin method a new name.
- A Mixin macro only works on the class it was mixed into, and its subclasses, even though Laravel shares macros with parent classes.
- A Mixin macro can be called statically. Then `static::` calls go to the macroable class, and reading or writing a property throws a `LogicException`.

## Mixables

A Mixable is a subclass of a `Macroable` class with the `Mixable` trait. Its public, non-static methods become macros on the parent class.

```php
namespace App\Models\Collections;

use AdrianSpacely\Mixable\Mixable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UserCollection extends EloquentCollection
{
    use Mixable;

    public function admins(): static
    {
        return $this->filter(fn ($user) => $user->is_admin);
    }
}

// In a service provider:
UserCollection::mix(); // Registers on EloquentCollection, the parent class.
```

When a Mixable method is called as a macro on a parent instance:

1. The subclass is created **without its constructor**, then `bootMixable()` runs.
2. The parent's properties are copied into it. The copy is shallow, so objects inside properties are shared.
3. The method runs on the subclass.
4. The subclass's properties are always copied back to the parent, even when the method throws.
5. If the method returned `$this`, the macro returns the parent. Anything else, including a new instance, is returned as it is.

- Put constructor-style setup in `bootMixable()`, or override `fromMacroable($parent)` (in) and `toMacroable()` (out) to build the instances yourself.
- Called directly on a subclass instance, a Mixable method is an ordinary method. Nothing is copied.
- `mix()` throws if a Mixable method overrides a method of the parent. Give it a new name.
- A Mixable macro only works on instances of its parent class, and their subclasses. A Mixable on `Eloquent\Collection` throws `BadMethodCallException` on a plain `collect()` collection. Models return a plain `Eloquent\Collection` unless they override `newCollection()`.
- `$this->inScope(fn () => ...)` runs the callback on the parent, syncing state in and out.

## Classes that aren't Macroable

Register the Mixin or Mixable on the class (or a parent class or interface), then call through `mixed()`:

```php
use App\Mixins\LoggerMixin;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

use function AdrianSpacely\Mixable\mixed;

LoggerMixin::mix(PendingBatch::class);

$batch = mixed(Bus::batch([]))
    ->name('Imports')
    ->info()
    ->onQueue('imports');

$batch->mixedTarget()->dispatch(); // The PendingBatch itself.
```

- `mixed($object)` returns an `AdrianSpacely\Mixable\Mixture`. Other method calls and properties are passed to the object.
- When a call returns the object, the Mixture is returned instead, so the chain keeps its mixins.
- A Mixture is not an instance of the object's class. Call `->mixedTarget()` before passing it to type-hinted code.

## By-reference arguments

Macros are called through `__call()`, which receives arguments by value. Wrap by-reference arguments with `ref()`:

```php
use function AdrianSpacely\Mixable\ref;

$total = 10;

$player->addPoints($total);      // $total is still 10
$player->addPoints(ref($total)); // $total is updated
```

`ref()` also works through `mixed()`. Don't use it when calling a Mixable method directly on a subclass instance; it's a real method there.

## Testing

Macros and registered mixins are global, so reset them between tests:

```php
use AdrianSpacely\Mixable\Mixer;
use Illuminate\Support\Collection;

afterEach(function () {
    Collection::flushMacros();
    Mixer::forgetMixins();
});

it('turns a collection into a sentence', function () {
    \App\Mixins\CollectionMixin::mix(Collection::class);

    expect(collect(['tea', 'coffee'])->toSentence())->toBe('tea and coffee');
});
```

Test through the macroable, the way the app calls it, rather than by instantiating the Mixin.

## Static analysis

- The package's PHPStan extension describes macros and `mixed()` objects with the real method types. `static` and `$this` return types become the macroable, or `Mixture<T>`.
- It needs Larastan, which boots the app so `mix()` has run. It must be included before Larastan's extension. phpstan/extension-installer does that automatically.
- A by-reference argument passed without `ref()` is reported with the identifier `mixable.refArgument`.
