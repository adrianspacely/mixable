<?php

namespace AdrianBrown\Mixable;

use Closure;
use ReflectionClass;
use ReflectionMethod;

class Mixer
{
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
                    && in_array($method->getName(), ['newMacroableInstance', 'newMixableInstance']) === false;
            })
            ->map->getName();

        foreach ($methods as $method) {
            $macroable::macro($method, function (...$args) use ($method, $createMixinInstance) {
                /** @var object|null $macroableInstance */
                $macroableInstance = isset($this) ? $this : null; // @phpstan-ignore-line
                $mixin = $createMixinInstance($macroableInstance, static::class);
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
