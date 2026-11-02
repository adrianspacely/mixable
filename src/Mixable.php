<?php

namespace AdrianSpacely\Mixable;

use Closure;
use InvalidArgumentException;

trait Mixable
{
    /** @var object|null */
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

        Mixer::mixin($mixin, $macroable, fn ($parent) => $mixin::fromMacroable($parent));
    }

    /**
     * @return mixed
     */
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

    /**
     * In: create the mixable for the parent a macro was called on, without its constructor, and copy the parent's state into it.
     *
     * @param object|null $parent
     * @return static
     */
    protected static function fromMacroable($parent)
    {
        $mixable = Mixer::newInstanceWithoutConstructor(static::class);
        $mixable->macroableInstance = $parent;
        $mixable->pullMacroableState();
        $mixable->bootMixable();

        return $mixable;
    }

    /**
     * Out: copy the mixable's state back to the parent, and return the parent for the macro to return when the method returned `$this`.
     *
     * @return object
     */
    public function toMacroable()
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

        while ($class !== false && ! $this->macroableInstance instanceof $class) {
            $class = get_parent_class($class);
        }

        return $class === false ? static::class : $class;
    }

    /**
     * @return void
     */
    public function bootMixable()
    {
    }
}
