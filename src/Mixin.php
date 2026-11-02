<?php

namespace AdrianSpacely\Mixable;

use AdrianSpacely\Mixable\Concerns\ForwardsScopedCalls;
use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

trait Mixin
{
    use ForwardsScopedCalls;

    /** @var object|null */
    protected $macroableInstance;

    /** @var class-string|null */
    protected $macroableClass;

    /**
     * @param class-string|list<class-string>|null $macroables
     */
    public static function mix($macroables = null): void
    {
        $defaults = (new ReflectionClass(static::class))->getDefaultProperties();

        $macroables = collect()
            ->wrap($defaults['macroable'] ?? '')
            ->merge(collect()->wrap($macroables ?? []))
            ->merge(collect()->wrap($defaults['macroables'] ?? []))
            ->filter();

        $mixin = get_called_class();

        foreach ($macroables as $macroable) {
            if (is_subclass_of($mixin, $macroable)) {
                $mixinTrait = Mixin::class;
                $mixableTrait = Mixable::class;

                throw new InvalidArgumentException(
                    "Cannot mixin [{$mixin}] because it is a subclass of [{$macroable}]. Instead of the [{$mixinTrait}] trait, use the [{$mixableTrait}] trait."
                );
            }

            Mixer::mixin($mixin, $macroable, fn ($macroable, $macroableClass) => $mixin::fromMacroable($macroable, $macroableClass));
        }
    }

    /**
     * In: create the mixin that decorates the object a macro was called on.
     *
     * @param object|null $macroableInstance
     * @param class-string|null $macroableClass
     * @return self
     */
    public static function fromMacroable($macroableInstance, ?string $macroableClass = null)
    {
        $mixin = new self();
        $mixin->macroableInstance = $macroableInstance;
        $mixin->macroableClass = $macroableClass;

        return $mixin;
    }

    /**
     * Out: return the decorated object for the macro to return when the method returned `$this`.
     *
     * @return object|null
     */
    public function toMacroable()
    {
        return $this->macroableInstance;
    }

    /**
     * @return mixed
     */
    protected function inScope(Closure $callback)
    {
        if ($this->macroableInstance === null && $this->macroableClass !== null) {
            return Closure::bind($callback, null, $this->macroableClass)();
        }

        return Mixer::invade($this->macroableInstance, $callback, $this->macroableScope());
    }

    /**
     * Calls are scoped to the class the mixin was mixed into, so private members it declares are reachable on a subclass.
     *
     * @return class-string|null
     */
    protected function macroableScope(): ?string
    {
        $isClass = $this->macroableClass !== null && class_exists($this->macroableClass);

        return $isClass && $this->macroableInstance instanceof $this->macroableClass ? $this->macroableClass : null;
    }

    /**
     * @param  mixed  $object
     * @return class-string|null
     */
    protected function forwardedCallScope($object)
    {
        return $object === $this->macroableInstance ? $this->macroableScope() : null;
    }

    public function __get(string $attribute): mixed
    {
        if (in_array($attribute, ['macroable', 'macroables'])) {
            return [];
        }

        $this->ensureMacroableInstance('get', $attribute);

        return $this->inScope(fn () => $this->{$attribute});
    }

    public function __set(string $attribute, mixed $value): void
    {
        $this->ensureMacroableInstance('set', $attribute);

        $this->inScope(fn () => $this->{$attribute} = $value);
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if ($this->macroableInstance === null && $this->macroableClass !== null) {
            return $this->inScope(static fn () => static::{$method}(...$parameters));
        }

        return $this->forwardDecoratedCallTo($this->macroableInstance, $method, $parameters);
    }

    /**
     * Forwards `static::` calls made within the mixin to the object the mixin is decorating.
     *
     * @param array<array-key, mixed> $parameters
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $mixin = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0]['object'] ?? null;

        if (! $mixin instanceof self) {
            static::throwBadMethodCallException($method);
        }

        return Mixer::invade($mixin, fn (self $mixin) => $mixin->{$method}(...$parameters));
    }

    private function ensureMacroableInstance(string $action, string $attribute): void
    {
        if ($this->macroableInstance === null && $this->macroableClass !== null) {
            throw new LogicException(
                "Cannot {$action} [{$attribute}] on [{$this->macroableClass}] because the macro was called statically."
            );
        }
    }
}
