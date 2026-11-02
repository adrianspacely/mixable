<?php

namespace AdrianSpacely\Mixable;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class Mixer
{
    /** @var array<class-string, array<string, class-string>> */
    protected static array $mixins = [];

    /** @var array<class-string, array<string, Closure>> */
    protected static array $factories = [];

    /** @var array<class-string, bool> */
    protected static array $isMixinOrMixable = [];

    /** @var array<class-string, ReflectionClass<object>> */
    protected static array $reflectionClasses = [];

    /** @var array<string, list<ReflectionProperty>> */
    protected static array $stateProperties = [];

    public static function mixin(string $mixin, string $macroable, ?Closure $createMixinInstance = null): void
    {
        $createMixinInstance ??= fn () => resolve($mixin);

        $class = new ReflectionClass($mixin);
        $methods = collect($class->getMethods())
            ->filter(function (ReflectionMethod $method) use ($class) {
                return $method->isPublic()
                    && $method->isStatic() === false
                    && $method->isAbstract() === false
                    && $class->getName() === $method->getDeclaringClass()->getName()
                    && in_array($method->getName(), static::traitMethods()) === false;
            })
            ->map->getName();

        foreach ($methods as $method) {
            static::ensureMethodDoesNotClash($mixin, $macroable, $method);
        }

        foreach ($methods as $method) {
            static::$mixins[$macroable][$method] = $mixin;
            static::$factories[$macroable][$method] = $createMixinInstance;

            // A class without macros can still be mixed through `mixed()`.
            if (! method_exists($macroable, 'macro')) {
                continue;
            }

            $macroable::macro($method, function (...$args) use ($macroable, $method, $createMixinInstance) {
                /** @var object|null $macroableInstance */
                $macroableInstance = isset($this) ? $this : null; // @phpstan-ignore-line

                return Mixer::callMixin($createMixinInstance, $macroableInstance, $macroable, $method, $args);
            });
        }
    }

    /**
     * PHP only calls `__call()` for methods that don't exist or can't be reached, so a macro can't share a method's name.
     *
     * @throws InvalidArgumentException
     */
    protected static function ensureMethodDoesNotClash(string $mixin, string $macroable, string $method): void
    {
        if (! method_exists($macroable, $method)) {
            return;
        }

        $existing = new ReflectionMethod($macroable, $method);
        $visibility = $existing->isPublic() ? 'public' : ($existing->isProtected() ? 'protected' : 'private');
        $reason = $existing->isPublic()
            ? 'PHP calls the method on the class before its macros, so the macro would never be called.'
            : "The macro would be called from outside the class, and the class's own method from inside it.";

        throw new InvalidArgumentException(
            "Cannot mixin [{$mixin}::{$method}()] because [{$macroable}] already has a {$visibility} {$method}() method. {$reason} Rename the mixin method."
        );
    }

    /**
     * Call a mixin method for the macroable, the way its macro does.
     *
     * @internal
     *
     * @param array<array-key, mixed> $args
     */
    public static function callMixin(Closure $createMixinInstance, ?object $macroableInstance, string $macroableClass, string $method, array $args): mixed
    {
        $args = Ref::unwrap($args);
        $mixin = $createMixinInstance($macroableInstance, $macroableClass);

        if ($macroableInstance === null || ! static::isMixinOrMixable($mixin)) {
            return $mixin->{$method}(...$args);
        }

        // Copy the state back even when the method throws, like a method on the macroable would keep its changes.
        try {
            $result = $mixin->{$method}(...$args);
        } finally {
            $macroable = $mixin->toMacroable();
        }

        return $result === $mixin ? $macroable : $result;
    }

    protected static function isMixinOrMixable(object $mixin): bool
    {
        return static::$isMixinOrMixable[get_class($mixin)] ??= (function () use ($mixin) {
            $traits = class_uses_recursive($mixin);

            return in_array(Mixin::class, $traits) || in_array(Mixable::class, $traits);
        })();
    }

    /**
     * Create an instance of the class without calling its constructor.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     * @return T
     */
    public static function newInstanceWithoutConstructor(string $class): object
    {
        static::$reflectionClasses[$class] ??= new ReflectionClass($class);

        /** @var T */
        return static::$reflectionClasses[$class]->newInstanceWithoutConstructor();
    }

    /**
     * Whether a mixin method was mixed into the object's class, a parent class, or an interface it implements.
     */
    public static function hasMixin(object $target, string $method): bool
    {
        return static::findMixinFactory($target, $method) !== null;
    }

    /**
     * Call a mixin method for the object, like a macro would, whether or not its class is macroable.
     *
     * @param array<array-key, mixed> $args
     *
     * @throws BadMethodCallException
     */
    public static function call(object $target, string $method, array $args): mixed
    {
        [$macroable, $createMixinInstance] = static::findMixinFactory($target, $method) ?? throw new BadMethodCallException(
            sprintf('Call to undefined method %s::%s()', get_class($target), $method)
        );

        return static::callMixin($createMixinInstance, $target, $macroable, $method, $args);
    }

    /**
     * @return array{class-string, Closure}|null The class the method was mixed into, and the mixin factory.
     */
    protected static function findMixinFactory(object $target, string $method): ?array
    {
        foreach (static::$factories as $macroable => $factories) {
            if (isset($factories[$method]) && $target instanceof $macroable) {
                return [$macroable, $factories[$method]];
            }
        }

        return null;
    }

    /**
     * The methods the package's traits add to a mixin, which are not macros.
     *
     * @return list<string>
     */
    protected static function traitMethods(): array
    {
        return collect([Mixin::class, Mixable::class])
            ->flatMap(fn (string $trait) => class_uses_recursive($trait) + [$trait => $trait])
            ->flatMap(fn (string $trait) => get_class_methods($trait))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The methods mixed in by `mixin()`, keyed by macroable class then method name, with the mixin class as the value.
     *
     * @return array<class-string, array<string, class-string>>
     */
    public static function mixins(): array
    {
        return static::$mixins;
    }

    public static function forgetMixins(): void
    {
        static::$mixins = [];
        static::$factories = [];
    }

    /**
     * @param object|class-string|null $target
     * @param class-string|null $scope The class scope to call the callback in, which defaults to the target's class.
     * @return mixed
     */
    public static function invade($target, Closure $callback, ?string $scope = null)
    {
        if ($target === null) {
            $callback = Closure::bind($callback, null, null);
        } else {
            $target = is_object($target) ? $target : resolve($target);
            $callback = Closure::bind($callback, $target, $scope ?? get_class($target));
        }

        return $callback($target);
    }

    /**
     * Copy the non-static properties declared on the given class and its parents from one object to another.
     */
    public static function copyState(object $from, object $to, string $class): void
    {
        foreach (static::stateProperties($class) as $property) {
            if (! $property->isInitialized($from)) {
                continue;
            }

            $value = $property->getValue($from);

            if ($property->isInitialized($to) && $property->getValue($to) === $value) {
                continue;
            }

            if ($property->isReadOnly()) {
                // A readonly property can only be initialised from the scope of the class that declares it.
                $name = $property->getName();
                Closure::bind(fn () => $to->{$name} = $value, null, $property->getDeclaringClass()->getName())();
            } else {
                $property->setValue($to, $value);
            }
        }
    }

    /**
     * The non-static properties declared on the class and its parents.
     *
     * @return list<ReflectionProperty>
     */
    protected static function stateProperties(string $class): array
    {
        if (isset(static::$stateProperties[$class])) {
            return static::$stateProperties[$class];
        }

        $properties = [];
        $declaringClass = $class;

        do {
            foreach ((new ReflectionClass($declaringClass))->getProperties() as $property) {
                if (! $property->isStatic() && $property->getDeclaringClass()->getName() === $declaringClass) {
                    $properties[] = $property;
                }
            }
        } while ($declaringClass = get_parent_class($declaringClass));

        return static::$stateProperties[$class] = $properties;
    }
}
