<?php

namespace AdrianBrown\Mixable;

/**
 * Holds a reference to a variable, so a macro can pass it to a by-reference parameter.
 *
 * PHP passes arguments to `__call()` and `__callStatic()` by value, so macros can't take references.
 * An object gets through, and the macro unwraps it back into a reference.
 *
 * @template T
 */
final class Ref
{
    /** @var T */
    public $value;

    /**
     * @param T $value
     */
    public function __construct(&$value)
    {
        $this->value = &$value;
    }

    /**
     * Swap each `Ref` in the arguments for a reference to the variable it holds.
     *
     * @param array<array-key, mixed> $args
     * @return array<array-key, mixed>
     */
    public static function unwrap(array $args): array
    {
        foreach ($args as $key => $arg) {
            if ($arg instanceof self) {
                $args[$key] = &$arg->value;
            }
        }

        return $args;
    }
}
