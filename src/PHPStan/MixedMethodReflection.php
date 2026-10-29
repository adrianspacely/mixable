<?php

namespace AdrianBrown\Mixable\PHPStan;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

/**
 * A mixin method, described as a method of the macroable it was mixed into.
 *
 * The macro returns the macroable when the mixin method returns itself, so `$this`, `static`,
 * and `self` return types become the macroable. By-reference parameters take arguments wrapped with `ref()`.
 */
class MixedMethodReflection implements MethodReflection
{
    public function __construct(
        private ClassReflection $calledOn,
        private ClassReflection $macroable,
        private ExtendedMethodReflection $mixinMethod,
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->macroable;
    }

    public function isStatic(): bool
    {
        // Macros can be called statically too.
        return true;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return $this->mixinMethod->getDocComment();
    }

    public function getName(): string
    {
        return $this->mixinMethod->getName();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * @return list<ParametersAcceptor>
     */
    public function getVariants(): array
    {
        return array_map(fn (ParametersAcceptor $variant) => new FunctionVariant(
            $variant->getTemplateTypeMap(),
            $variant->getResolvedTemplateTypeMap(),
            array_map(fn (ParameterReflection $parameter) => $this->toMacroParameter($parameter), $variant->getParameters()),
            $variant->isVariadic(),
            $this->toMacroable($variant->getReturnType()),
        ), $this->mixinMethod->getVariants());
    }

    public function isDeprecated(): TrinaryLogic
    {
        return $this->mixinMethod->isDeprecated();
    }

    public function getDeprecatedDescription(): ?string
    {
        return $this->mixinMethod->getDeprecatedDescription();
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return $this->mixinMethod->isInternal();
    }

    public function getThrowType(): ?Type
    {
        return $this->mixinMethod->getThrowType();
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return $this->mixinMethod->hasSideEffects();
    }

    /**
     * A macro gets its arguments by value, so a by-reference parameter takes its argument wrapped with `ref()`.
     */
    private function toMacroParameter(ParameterReflection $parameter): ParameterReflection
    {
        if ($parameter->passedByReference()->no()) {
            return $parameter;
        }

        return new RefParameterReflection($parameter);
    }

    private function toMacroable(Type $type): Type
    {
        $mixin = $this->mixinMethod->getDeclaringClass()->getName();

        return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($mixin): Type {
            if ($type->getObjectClassNames() === [$mixin]) {
                return new StaticType($this->calledOn);
            }

            return $traverse($type);
        });
    }
}
