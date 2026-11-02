<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use BadMethodCallException;

/**
 * Takes macros through `__callStatic('macro')` rather than a `macro()` method, like `Illuminate\Database\Eloquent\Builder`.
 */
class CallStaticMacroable
{
    public array $tags = [];

    protected static array $macros = [];

    public static function __callStatic($method, $parameters)
    {
        if ($method === 'macro') {
            static::$macros[$parameters[0]] = $parameters[1];

            return;
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }

    public function __call($method, $parameters)
    {
        if (! isset(static::$macros[$method])) {
            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }

        return static::$macros[$method]->bindTo($this, static::class)(...$parameters);
    }

    public static function forgetMacros(): void
    {
        static::$macros = [];
    }
}
