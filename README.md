⚠️ **This is still in development.** ⚠️

- [ ] Blog post.
- [ ] Finish that meme.

# Mixable

Nicer mixins for `Macroable` classes in Laravel.

*[ TODO: Link to blog post explaining the `Macorable` trait; how it does mixins; and what this package does differently. ]*

![Macroable Mixins vs. Mixable Mixins](art/meme.jpeg)

## Installation

You can install this package via composer:

```bash
composer require adrianb93/mixable
```

## Usage

There are two traits in this package, `Mixin` and `Mixable`. They make public methods available to `Macroable` classes.

- `Mixin` is used on a plain PHP class. You specify which `Macroable` classes it mixes into.
- `Mixable` is for a subclass of a `Macroable`. It mixes into the `Macroable` class it extends.

### Registering the Macros

You register the mixins in your `AppServiceProvider` like this:

```php
// Mixin: You specify which Macroable classes it mixes into.
\App\Mixins\CollectionMixin::mix([
    \Illuminate\Support\Collection::class,
]);

// Mixable: It mixes into the Macroable class it extends.
\App\Models\Builders\Builder::mix();
```

### Mixins

`AdrianBrown\Mixable\Mixin` is used on a plain PHP class. It macros public methods into Macroable classes you specify.

**Example Mixin:**

```php
namespace App\Mixins;

use AdrianBrown\Mixable\Mixin;

class LoggerMixin
{
    use Mixin;

    /**
     * Logs $this then returns $this.
     *
     * @param array $context
     * @return $this
     */
    public function info($context = [])
    {
        $message = match (true) {
            method_exists($this, 'toSql') => $this->toSql(),
            method_exists($this, 'toArray') => $this->toArray(),
            default => $this,
        };

        logger()->info($message, $context);

        return $this;
    }
}
```

The package will throw an exception if the Mixin is a subclass of the Macroable. It will instruct you to use the `Mixable` trait instead.

**Quick Mixin Facts:**

- The `Mixin` trait is a decorator for the Macroable. Methods calls and attributes gets and sets are possible for private, protected, and public visibility.
- When returning `$this` (Mixin), the registered macro switches the return value to the Macroable.
- A decorator on the most part feels like it is Macroable, but it’s not in it’s scope. If you need to be in the Macroable scope, you can use `$this->inScope($callback)`. Example:

    ```php
    LoggerMixin::mix(Collection::class)

    class LoggerMixin
    {
        use Mixin;

        public function whoami(): string
        {
            static::class;
            // => "App\Mixins\LoggerMixin" (the mixin)

            return $this->inScope(function () {
                return static::class;
                // => "Illuminate\Support\Collection" (the macroable)
            });
        }
    }
    ```

- When you call a "Mixin macro" statically, there is no Macroable instance to decorate. `static::` calls are forwarded to the Macroable class, and `$this->inScope($callback)` runs in the Macroable class scope. Getting or setting attributes throws a `LogicException`.


### Mixables

`AdrianBrown\Mixable\Mixable` is for a subclass of a `Macroable`. It macros public methods into the parent class.

**Example Mixable:**

```php
namespace App\Models\Collections;

use AdrianBrown\Mixable\Mixable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Collection extends EloquentCollection
{
    use Mixable;

    public function whereBelongsTo($related, $relationshipName = null)
    {
        ...

        return $this;
    }
}
```

The package will throw an exception if the Mixable is not a subclass of the Macroable. It will instruct you to use the `Mixin` trait instead.

**Quick Mixable Facts:**

When you call a "Mixable macro":

1. The subclass (Mixable) is instantiated without a constructor.
2. The state of the parent (Macroable) is copied to the subclass (Mixable).
3. The macro’d subclass method is called.
4. The registered macro has the return value:
    1. The state of the subclass (Mixable) is copied to the parent (Macroable).
    2. If the return value is `$this` (Mixable), it is switched to the parent (Macroable).
5. The registered macro returns the value.

When you call a "Mixable macro" statically, there is no parent (Macroable) instance. The subclass (Mixable) is still instantiated without a constructor, but no state is copied in or out, and `$this` is returned as the subclass (Mixable).

When you call a method on an instance of the subclass (Mixable) (not via a "Mixable macro"):

- The `Mixable` trait does nothing. You’re in a normal ol’ instance.

The called method is scoped to the subclass (Mixable) in either case. If parent (Macroable) scope matters, then you can use `$this->inScope($callback)`:

```php
\App\Models\Collections\Collection::mix();

namespace \App\Models\Collections;

class Collection extends EloquentCollection
{
    use Mixable;

    public function whoami()
    {
        static::class; // => "App\Models\Collections\Collection" (the mixable)

        return $this->inScope(fn () => static::class);
    }
}
```

When the method is called from a parent instance (Macroable):

```php
\Illuminate\Database\Eloquent\Collection::make()->whoami();
// => "Illuminate\Database\Eloquent\Collection"
```

When the method is called from a subclass instance (Mixable):

```php
\App\Models\Collections\Collection::make()->whoami();
// => "App\Models\Collections\Collection"
```

If there is no parent (Macroable), `inScope($callback)` will not change the scope of the callback.

### More on Registering

**Registering Mixables**

The Macroable class the Mixable extends is what it registers the macros to. You register the Mixable in your `AppServiceProvider` like this:

```php
\App\Models\Collections\Collection::mix();
```

**Registering Mixins**

In your `AppServiceProvider`, call the `mix()` function on each class that uses the `Mixin` trait.

```php
use App\Mixins\LoggerMixin;

public function register()
{
    LoggerMixin::mix(Collection::class);

    // or

    LoggerMixin::mix([
        Builder::class,
        Request::class,
        Collection::class,
    ]);
}
```

You can also keep the Macroables inside the class which uses the `Mixin` trait. All you would need to do in `AppServiceProvider` is:

```php
public function register()
{
    LoggerMixin::mix();
}
```

...and the Mixin can hold the Macroables it should register itself onto:

```php
class LoggerMixin
{
    use Mixin;

    public $macroable = Collection::class;

    // or

    public $macroable = [
        Builder::class,
        Request::class,
        Collection::class,
    ];

    ...
}
```

## Static Analysis

The package includes a [PHPStan](https://phpstan.org) extension. If you use [phpstan/extension-installer](https://github.com/phpstan/extension-installer) it's enabled automatically, otherwise include it in your `phpstan.neon`:

```neon
includes:
    - vendor/adrianb93/mixable/extension.neon
```

**Macros have the mixin method's types:**

PHPStan sees each macro as the Mixin or Mixable method it calls, so arguments and return values are checked. `$this`, `static`, and `self` return types become the Macroable, because that is what the macro returns:

```php
class PointsMixin
{
    use Mixin;

    public function addPoints(int $points): static { ... }
}

$player->addPoints(5);     // Player
$player->addPoints('five'); // Error: Parameter #1 $points of method Player::addPoints() expects int, string given.
```

Macros are registered when your app boots, so this needs [Larastan](https://github.com/larastan/larastan), which boots your app before analysing it. Larastan also describes macros, but without their types, and PHPStan uses whichever extension is included first. phpstan/extension-installer includes packages in alphabetical order, so this one comes first. If you include the extensions yourself, include this one before Larastan's.

**By-reference parameters are reported:**

Macros receive arguments by value (see [troubleshooting](#mixin-mixable-my-macro-doesnt-update-an-argument-passed-by-reference)), so by-reference parameters on Mixin and Mixable methods are reported:

```
Parameter #1 $value of Mixin method App\Mixins\PointsMixin::addPoints() is passed by reference, but macros receive arguments by value.
```

The error identifier is `mixable.parameterByReference`. For a Mixable you only call on subclass instances, you can ignore it.

## Troubleshooting

### [Mixable] My Mixin isn’t returning an instance of the parent (Macroable), it is returning an instance of the child/subclass (Mixable).

A good example of this is an immutable Macroable like `Illuminate\Support\Collection`. Most methods return a new Collection instance. This is not the same instance.

If the return value is not the same instance as the initial Mixable instance, then we do not swap the return value to the Macroable. The Mixable’s state is still copied back to the Macroable instance the macro was called on.

### [Mixin] PHP Warning: Indirect modification of overloaded property has no effect

`Mixin` is a decorator meaning it uses the magic methods `__get()` and `__set()` to interact with the Macroable’s class attributes. When passing an attribute to a function that accepts a reference to a value, you run into this warning that the reference is indirect modification.

There are a couple of ways around this issue:

1. Use `$this->inScope($callback)` placing your code within the callback. Property gets and sets within the callback are directly on the Macroable.
2. Copy the attribute to a local variable, then set that local variable to the attribute.

    ```php
    $items = $this->items;
    array_walk($items, fn (&$item) => $items = $item * 2);
    $this->items = $items;
    ```


### [Mixin] [Mixable] My macro doesn’t update an argument passed by reference.

PHP passes arguments to `__call()` and `__callStatic()` by value, and macros are called through them. A parameter like `int &$value` gets a copy, so the variable you passed in is not changed. This is true for every Laravel macro, not only mixins.

```php
public function addPoints(int &$value): void
{
    $value = $value + $this->points;
}

$value = 10;
$player->addPoints($value); // $value is still 10
```

There are a few ways around it:

1. Return the value instead: `$value = $player->addPoints($value);`
2. Pass an object, because objects are passed by handle.
3. For a Mixable, call the method on an instance of the subclass. It is a real method there, so references work.

The PHPStan extension reports by-reference parameters on Mixin and Mixable methods (see [Static Analysis](#static-analysis)).

### [Mixable] When the subclass constructor has logic that is not getting triggered.

A Mixable, when called from a macro/Macroable, instantiates the subclass/Mixable **without a constructor**. The parent’s state is then copied to the child instance.

If you had logic in your constructor that is not getting triggered, then here are some solutions:

1. You could add a `bootMixable()` method to trigger the same setup code you do in your constructor.
2. Override the “in” and “out” methods `Mixable` implements and do it your way. The following example is how to make an eloquent query builder instance using another eloquent query builder instance.

    ```php
    protected static function newMixableInstance($parent): self
    {
        // IN: Create an instance of the mixable subclass which has the methods
        //     we mixed into the parent class.
        return (new \App\Models\Builders\Builder($parent->getQuery()))
            ->setModel($parent->getModel())
            ->mergeConstraintsFrom($parent);
    }

    public function newMacroableInstance(): BaseBuilder
    {
        // OUT: Return the macroable instance which the macro was called from.
        //      You could also return `$this` if you're fine with switching
        //      to an instance of the mixable subclass.
        return (new \Illuminate\Database\Eloquent\Builder($this->getQuery()))
            ->setModel($this->getModel())
            ->mergeConstraintsFrom($this);
    }
    ```

3. Use a `Mixin` instead. `Mixable` might not be the right fit for the `Macroable` you’re extending.

### [Mixin] [Mixable] `static::class` isn’t what I expected.

- `Mixin` is a decorator of the Macroable. It is a different class.
- `Mixable` is a subclass of the Macroable. It is a different class.

If you need `static::class` to give you the Macroable class, then use `$this->inScope($callback)`.

```php
public function whoami(): string
{
    // Before: return static::class;
    return $this->inScope(fn () => static::class);
}
```

### [Mixin] [Mixable] I passed `$this` to another class and it didn’t match the type hint.

- `Mixin` is a decorator of the Macroable. It is a different class.
- `Mixable` is a subclass of the Macroable. It is a different class.

If you need `$this` to be the Macroable instance, then use `$this->inScope($callback)`.

```php
public function notify(): void
{
    // Before: ExampleNoticiation::notify($this);
    $this->inScope(fn () => ExampleNoticiation::notify($this));
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Adrian Brown](https://github.com/adrianb93)
- [All Contributors](../../contributors)

I like to use dedicated query builders and collections for my models. I have a base collection and query builder with some awesome methods. I use the `Mixable` trait on them and created this package because of them. You can learn more about dedicated collections and query builders for eloquent models on [Tim MacDonald's](https://twitter.com/timacdonald87) blog:

- [Follow the Eloquent road](https://timacdonald.me/follow-the-eloquent-road-laracon-talk/)
- [Giving collections a voice](https://timacdonald.me/giving-collections-a-voice/)
- [Dedicated query builders for Eloquent models](https://timacdonald.me/giving-collections-a-voice/)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
