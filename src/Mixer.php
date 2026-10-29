<?php

namespace AdrianBrown\Mixable;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

class Mixer
{
    /** @var array<class-string, array<string, class-string>> */
    protected static array $mixins = [];

    /** @var array<class-string, array<string, Closure>> */
    protected static array $factories = [];

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
        $result = $mixin->{$method}(...$args);
        $traits = class_uses_recursive($mixin);
        $isMixinOrMixable = in_array(Mixin::class, $traits) || in_array(Mixable::class, $traits);

        if ($macroableInstance === null || ! $isMixinOrMixable) {
            return $result;
        }

        $macroable = $mixin->newMacroableInstance();

        return $result === $mixin ? $macroable : $result;
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
        do {
            foreach ((new ReflectionClass($class))->getProperties() as $property) {
                if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if (! $property->isInitialized($from)) {
                    continue;
                }

                $value = $property->getValue($from);

                if ($property->isInitialized($to) && $property->getValue($to) === $value) {
                    continue;
                }

                $name = $property->getName();
                Closure::bind(fn () => $to->{$name} = $value, null, $class)();
            }
        } while ($class = get_parent_class($class));
    }
}
