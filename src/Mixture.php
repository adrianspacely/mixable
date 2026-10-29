<?php

namespace AdrianSpacely\Mixable;

use BadMethodCallException;

/**
 * Mixes mixins into any object, including one whose class isn't macroable.
 *
 * Calls to mixin methods are handled like macros. Other calls and properties are forwarded to the object.
 * When the object returns itself, the mixture is returned instead, so the chain keeps its mixins.
 *
 * @template T of object
 */
class Mixture
{
    /**
     * @param T $target
     */
    public function __construct(private object $target)
    {
    }

    /**
     * @return T
     */
    public function mixedTarget(): object
    {
        return $this->target;
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (Mixer::hasMixin($this->target, $method)) {
            $result = Mixer::call($this->target, $method, $parameters);
        } elseif (is_callable([$this->target, $method])) {
            $result = $this->target->{$method}(...Ref::unwrap($parameters));
        } else {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()',
                get_class($this->target),
                $method
            ));
        }

        return $result === $this->target ? $this : $result;
    }

    public function __get(string $property): mixed
    {
        return $this->target->{$property};
    }

    public function __set(string $property, mixed $value): void
    {
        $this->target->{$property} = $value;
    }

    public function __isset(string $property): bool
    {
        return isset($this->target->{$property});
    }

    public function __unset(string $property): void
    {
        unset($this->target->{$property});
    }
}
