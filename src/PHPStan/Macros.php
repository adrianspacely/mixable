<?php

namespace AdrianBrown\Mixable\PHPStan;

use AdrianBrown\Mixable\Mixer;
use AdrianBrown\Mixable\Mixture;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

/**
 * Finds the method a macro or a mixture calls, from the mixins `mix()` registered.
 */
class Macros
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * Describe a macro, or a method called on a mixture, with the types of the method it calls.
     */
    public function describe(ClassReflection $calledOn, string $methodName): ?MixedMethodReflection
    {
        if ($calledOn->getName() === Mixture::class) {
            return $this->describeMixtureMethod($calledOn, $methodName);
        }

        [$macroable, $mixin] = static::find($calledOn, $methodName) ?? [null, null];

        if ($macroable === null || $mixin === null) {
            return null;
        }

        $mixinMethod = $this->reflectionProvider->getClass($mixin)->getNativeMethod($methodName);

        // The macro returns the macroable when the mixin method returns itself.
        return new MixedMethodReflection(
            $this->reflectionProvider->getClass($macroable),
            $mixinMethod,
            fn (Type $type) => $this->replaceClass($type, [$mixin], new StaticType($calledOn)),
            true,
        );
    }

    private function describeMixtureMethod(ClassReflection $mixture, string $methodName): ?MixedMethodReflection
    {
        $targetType = $mixture->getActiveTemplateTypeMap()->getType('T');

        if ($targetType === null) {
            return null;
        }

        $mixtureType = new GenericObjectType(Mixture::class, [$targetType]);

        foreach ($targetType->getObjectClassReflections() as $target) {
            [, $mixin] = static::find($target, $methodName, macrosOnly: false) ?? [null, null];

            // A mixin method returning itself returns the object, which the mixture returns itself for.
            if ($mixin !== null) {
                return new MixedMethodReflection(
                    $mixture,
                    $this->reflectionProvider->getClass($mixin)->getNativeMethod($methodName),
                    fn (Type $type) => $this->replaceClass($type, [$mixin], $mixtureType),
                    false,
                );
            }

            if ($target->hasNativeMethod($methodName) && $target->getNativeMethod($methodName)->isPublic()) {
                return new MixedMethodReflection(
                    $mixture,
                    $target->getNativeMethod($methodName),
                    fn (Type $type) => $this->replaceStatic($type, $target, $mixtureType),
                    false,
                );
            }
        }

        return null;
    }

    /**
     * The method a macro or a mixture calls, to check its parameters.
     */
    public function calledMethod(ClassReflection $calledOn, string $methodName): ?ExtendedMethodReflection
    {
        if ($calledOn->getName() === Mixture::class) {
            foreach ($calledOn->getActiveTemplateTypeMap()->getType('T')?->getObjectClassReflections() ?? [] as $target) {
                [, $mixin] = static::find($target, $methodName, macrosOnly: false) ?? [null, null];

                if ($mixin !== null) {
                    return $this->reflectionProvider->getClass($mixin)->getNativeMethod($methodName);
                }

                if ($target->hasNativeMethod($methodName)) {
                    return $target->getNativeMethod($methodName);
                }
            }

            return null;
        }

        [, $mixin] = static::find($calledOn, $methodName) ?? [null, null];

        return $mixin === null ? null : $this->reflectionProvider->getClass($mixin)->getNativeMethod($methodName);
    }

    /**
     * @param bool $macrosOnly Only find methods which were registered as macros, rather than only being available through `mixed()`.
     * @return array{class-string, class-string}|null The class the method was mixed into, and the mixin class.
     */
    public static function find(ClassReflection $classReflection, string $methodName, bool $macrosOnly = true): ?array
    {
        foreach (Mixer::mixins() as $macroable => $methods) {
            if (! isset($methods[$methodName]) || ! $classReflection->is($macroable)) {
                continue;
            }

            $isMacro = method_exists($macroable, 'hasMacro') && $macroable::hasMacro($methodName);

            if ($macrosOnly && ! $isMacro) {
                continue;
            }

            return [$macroable, $methods[$methodName]];
        }

        return null;
    }

    /**
     * @param list<string> $classNames
     */
    private function replaceClass(Type $type, array $classNames, Type $replacement): Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($classNames, $replacement): Type {
            if ($type->getObjectClassNames() !== [] && array_diff($type->getObjectClassNames(), $classNames) === []) {
                return $replacement;
            }

            return $traverse($type);
        });
    }

    private function replaceStatic(Type $type, ClassReflection $target, Type $replacement): Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($target, $replacement): Type {
            if ($type instanceof StaticType && $target->is($type->getClassName())) {
                return $replacement;
            }

            return $traverse($type);
        });
    }
}
