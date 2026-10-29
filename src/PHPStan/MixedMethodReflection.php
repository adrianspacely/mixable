<?php

namespace AdrianSpacely\Mixable\PHPStan;

use Closure;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * A method called through `__call()`, like a macro's mixin method, described as a method of the class it is called on.
 *
 * Its return type is mapped, so a method which returns itself can return what the call returns instead.
 * By-reference parameters take arguments wrapped with `ref()`, because `__call()` gets arguments by value.
 */
class MixedMethodReflection implements MethodReflection
{
    /**
     * @param Closure(Type): Type $mapReturnType
     */
    public function __construct(
        private ClassReflection $declaringClass,
        private ExtendedMethodReflection $mixinMethod,
        private Closure $mapReturnType,
        private bool $isStatic,
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
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
            ($this->mapReturnType)($variant->getReturnType()),
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
}
