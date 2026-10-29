<?php

namespace AdrianBrown\Mixable;

use Closure;
use InvalidArgumentException;
use ReflectionClass;

trait Mixable
{
    protected $macroableInstance;

    public static function mix(?string $macroable = null): void
    {
        $mixin = get_called_class();
        $macroable ??= get_parent_class($mixin);

        if (! is_subclass_of($mixin, $macroable)) {
            $mixinTrait = Mixin::class;
            $mixableTrait = Mixable::class;

            throw new InvalidArgumentException(
                "Cannot mixin [{$mixin}] because it is not a subclass of [{$macroable}]. Instead of the [{$mixableTrait}] trait, use the [{$mixinTrait}] trait."
            );
        }

        Mixer::mixin($mixin, $macroable, fn ($parent) => $mixin::newMixableInstance($parent));
    }

    protected function inScope(Closure $callback)
    {
        if ($this->macroableInstance === null) {
            return Mixer::invade($this, $callback);
        }

        $this->pushMacroableState();

        try {
            return Mixer::invade($this->macroableInstance, $callback);
        } finally {
            $this->pullMacroableState();
        }
    }

    protected static function newMixableInstance($parent)
    {
        $mixable = (new ReflectionClass(get_called_class()))->newInstanceWithoutConstructor();
        $mixable->macroableInstance = $parent;
        $mixable->pullMacroableState();
        $mixable->bootMixable();

        return $mixable;
    }

    public function newMacroableInstance()
    {
        $this->pushMacroableState();

        return $this->macroableInstance ?? $this;
    }

    protected function pullMacroableState(): void
    {
        if ($this->macroableInstance !== null) {
            Mixer::copyState($this->macroableInstance, $this, $this->sharedMacroableClass());
        }
    }

    protected function pushMacroableState(): void
    {
        if ($this->macroableInstance !== null) {
            Mixer::copyState($this, $this->macroableInstance, $this->sharedMacroableClass());
        }
    }

    private function sharedMacroableClass(): string
    {
        $class = get_parent_class($this);

        while (! $this->macroableInstance instanceof $class) {
            $class = get_parent_class($class);
        }

        return $class;
    }

    public function bootMixable()
    {
    }
}
