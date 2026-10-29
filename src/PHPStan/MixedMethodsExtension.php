<?php

namespace AdrianBrown\Mixable\PHPStan;

use AdrianBrown\Mixable\Mixer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Describes the macros `mix()` registered with the types of the mixin methods they call.
 *
 * Macros are registered when the app boots, so this needs PHPStan to boot the app first, like Larastan does.
 */
class MixedMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->findMixin($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        [$macroable, $mixin] = $this->findMixin($classReflection, $methodName);

        return new MixedMethodReflection(
            $classReflection,
            $this->reflectionProvider->getClass($macroable),
            $this->reflectionProvider->getClass($mixin)->getNativeMethod($methodName),
        );
    }

    /**
     * @return array{class-string, class-string}|null
     */
    private function findMixin(ClassReflection $classReflection, string $methodName): ?array
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
