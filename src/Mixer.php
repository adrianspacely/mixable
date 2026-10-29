<?php

namespace AdrianBrown\Mixable;

use Closure;
use ReflectionClass;
use ReflectionMethod;

class Mixer
{
    /** @var array<class-string, array<string, class-string>> */
    protected static array $mixins = [];

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

            $macroable::macro($method, function (...$args) use ($method, $createMixinInstance) {
                /** @var object|null $macroableInstance */
                $macroableInstance = isset($this) ? $this : null; // @phpstan-ignore-line
                $mixin = $createMixinInstance($macroableInstance, static::class);
                foreach ($args as $key => $arg) {
                    if ($arg instanceof Ref) {
                        $args[$key] = &$arg->value;
                    }
                }

                $result = $mixin->{$method}(...$args);
                $traits = class_uses_recursive($mixin);
                $isMixinOrMixable = in_array(Mixin::class, $traits) || in_array(Mixable::class, $traits);

                if ($macroableInstance === null || ! $isMixinOrMixable) {
                    return $result;
                }

                $macroable = $mixin->newMacroableInstance();

                return $result === $mixin ? $macroable : $result;
            });
        }
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
     * The macros registered by `mixin()`, keyed by macroable class then method name, with the mixin class as the value.
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
    }

    /**
     * @param object|class-string|null $target
     * @return mixed
     */
    public static function invade($target, Closure $callback)
    {
        if ($target === null) {
            $callback = Closure::bind($callback, null, null);
        } else {
            $target = is_object($target) ? $target : resolve($target);
            $callback = Closure::bind($callback, $target, get_class($target));
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
