<?php

namespace AdrianSpacely\Mixable\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

/**
 * Describes the macros `mix()` registered, and the methods of a `mixed()` object, with the types of the methods they call.
 *
 * Mixins are registered when the app boots, so this needs PHPStan to boot the app first, like Larastan does.
 */
class MixedMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private Macros $macros)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->macros->describe($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->macros->describe($classReflection, $methodName)
            ?? throw new \LogicException("No mixed method [{$methodName}] on [{$classReflection->getName()}].");
    }
}
