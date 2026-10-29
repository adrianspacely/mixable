<?php

namespace AdrianBrown\Mixable;

use AdrianBrown\Mixable\Concerns\ForwardsScopedCalls;
use Closure;
use InvalidArgumentException;
use LogicException;

trait Mixin
{
    use ForwardsScopedCalls;

    protected $macroableInstance;

    protected $macroableClass;

    public static function mix($macroables = null): void
    {
        $macroables = collect()
            ->wrap((new static())->macroable ?? '')
            ->merge(collect()->wrap($macroables ?? []))
            ->merge(collect()->wrap((new static())->macroables ?? []))
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

            Mixer::mixin($mixin, $macroable, fn ($macroable, $macroableClass) => $mixin::newMixableInstance($macroable, $macroableClass));
        }
    }

    public static function newMixableInstance($macroableInstance, ?string $macroableClass = null)
    {
        return tap(new self(), function ($mixin) use ($macroableInstance, $macroableClass) {
            $mixin->macroableInstance = $macroableInstance;
            $mixin->macroableClass = $macroableClass;
        });
    }

    public function newMacroableInstance()
    {
        return $this->macroableInstance;
    }

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

    protected function forwardedCallScope($object)
    {
        return $object === $this->macroableInstance ? $this->macroableScope() : null;
    }

    public function __get($attribute)
    {
        if (in_array($attribute, ['macroable', 'macroables'])) {
            return [];
        }

        $this->ensureMacroableInstance('get', $attribute);

        return $this->inScope(fn () => $this->{$attribute});
    }

    public function __set($attribute, $value)
    {
        $this->ensureMacroableInstance('set', $attribute);

        $this->inScope(fn () => $this->{$attribute} = $value);
    }

    public function __call($method, $parameters)
    {
        if ($this->macroableInstance === null && $this->macroableClass !== null) {
            return $this->inScope(static fn () => static::{$method}(...$parameters));
        }

        return $this->forwardDecoratedCallTo($this->macroableInstance, $method, $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $mixin = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0]['object'];

        return Mixer::invade($mixin, fn () => $this->{$method}(...$parameters));
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
