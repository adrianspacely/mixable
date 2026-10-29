<?php

namespace AdrianBrown\Mixable\PHPStan;

use AdrianBrown\Mixable\Ref;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * A by-reference parameter of a macro, which takes its argument wrapped with `ref()`.
 *
 * A plain value is accepted too, because PHP accepts it, but it is reported by `RefArgumentRule`.
 */
class RefParameterReflection implements ParameterReflection
{
    public function __construct(private ParameterReflection $parameter)
    {
    }

    public function getName(): string
    {
        return $this->parameter->getName();
    }

    public function isOptional(): bool
    {
        return $this->parameter->isOptional();
    }

    public function getType(): Type
    {
        $type = $this->parameter->getType();

        return TypeCombinator::union($type, new GenericObjectType(Ref::class, [$type]));
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return $this->parameter->isVariadic();
    }

    public function getDefaultValue(): ?Type
    {
        return $this->parameter->getDefaultValue();
    }
}
