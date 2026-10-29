<?php

namespace AdrianBrown\Mixable\PHPStan;

use AdrianBrown\Mixable\Mixer;
use PHPStan\Reflection\ClassReflection;

/**
 * Finds the mixin method a macro calls, from the macros `mix()` registered.
 */
class Macros
{
    /**
     * @return array{class-string, class-string}|null The macroable class and the mixin class.
     */
    public static function find(ClassReflection $classReflection, string $methodName): ?array
    {
        foreach (Mixer::mixins() as $macroable => $methods) {
            if (! isset($methods[$methodName]) || ! $classReflection->is($macroable)) {
                continue;
            }

            if (! method_exists($macroable, 'hasMacro') || ! $macroable::hasMacro($methodName)) {
                continue;
            }

            return [$macroable, $methods[$methodName]];
        }

        return null;
    }
}
