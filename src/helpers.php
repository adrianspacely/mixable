<?php

use AdrianBrown\Mixable\Mixture;
use AdrianBrown\Mixable\Ref;

if (! function_exists('ref')) {
    /**
     * Wrap a variable so a macro can pass it to a by-reference parameter.
     *
     * @template T
     *
     * @param T $value
     * @return Ref<T>
     */
    function ref(&$value): Ref
    {
        return new Ref($value);
    }
}

if (! function_exists('mixed')) {
    /**
     * Mix mixins into an object, including one whose class isn't macroable.
     *
     * @template T of object
     *
     * @param T $target
     * @return Mixture<T>
     */
    function mixed(object $target): Mixture
    {
        return new Mixture($target);
    }
}
