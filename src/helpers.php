<?php

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
