<?php

namespace AdrianSpacely\Mixable\Concerns;

use AdrianSpacely\Mixable\Mixer;
use BadMethodCallException;
use Error;

trait ForwardsScopedCalls
{
    /**
     * Forward a method call to the given object under its scope.
     *
     * @param  mixed  $object
     * @param  string  $method
     * @param  array<array-key, mixed>  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function forwardCallTo($object, $method, $parameters)
    {
        try {
            return Mixer::invade($object, fn () => $this->{$method}(...$parameters), $this->forwardedCallScope($object));
        } catch (Error|BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (! preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['class'] != get_class($object) ||
                $matches['method'] != $method) {
                throw $e;
            }

            static::throwBadMethodCallException($method);
        }
    }

    /**
     * The class scope to forward calls to the given object in, which defaults to the object's class.
     *
     * @param  mixed  $object
     * @return class-string|null
     */
    protected function forwardedCallScope($object)
    {
        return null;
    }

    /**
     * Forward a method call to the given object under its scope, returning $this if the forwarded call returned itself.
     *
     * @param  mixed  $object
     * @param  string  $method
     * @param  array<array-key, mixed>  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function forwardDecoratedCallTo($object, $method, $parameters)
    {
        $result = $this->forwardCallTo($object, $method, $parameters);

        if ($result === $object) {
            return $this;
        }

        return $result;
    }

    /**
     * Throw a bad method call exception for the given method.
     *
     * @param  string  $method
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected static function throwBadMethodCallException($method)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            static::class,
            $method
        ));
    }
}
