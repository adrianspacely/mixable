## Mixable

`adrianspacely/mixable` adds methods to Laravel `Macroable` classes from ordinary PHP classes, and to any other object through `mixed()`.

- Use a **Mixin** (`AdrianSpacely\Mixable\Mixin`) on a plain class to add its public methods to the classes it names. Inside it, `$this` acts like the target object, including its protected and private members.
- Use a **Mixable** (`AdrianSpacely\Mixable\Mixable`) on a subclass of a `Macroable` class, like a dedicated Eloquent collection or query builder, to add its public methods to the parent class.
- Register each one once, in a service provider: `CollectionMixin::mix(Collection::class);` for a Mixin, or `UserCollection::mix();` for a Mixable.
- A method that returns `$this` returns the object the macro was called on, so chains keep working.
- For a class that isn't `Macroable`, register the Mixin the same way and call it through `mixed($object)`. Call `->mixedTarget()` before passing the result to code that type-hints the original class.
- Macros receive arguments by value. For a by-reference parameter, wrap the argument with `ref($value)`.
- `ref()` and `mixed()` are namespaced functions: `use function AdrianSpacely\Mixable\ref;` and `use function AdrianSpacely\Mixable\mixed;`.
- A Mixable subclass is created without its constructor when it is called as a macro. Put its setup in `bootMixable()`.
- Use the `mixable-development` skill when writing, changing, or testing a Mixin or Mixable.

@verbatim
<code-snippet name="Write and register a Mixin" lang="php">
use AdrianSpacely\Mixable\Mixin;
use Illuminate\Support\Collection;

class CollectionMixin
{
    use Mixin;

    public function toSentence(): string
    {
        $items = $this->values()->all();
        $last = array_pop($items);

        return $items ? implode(', ', $items) . " and {$last}" : (string) $last;
    }
}

// In a service provider's boot() method:
CollectionMixin::mix(Collection::class);

collect(['tea', 'coffee', 'cake'])->toSentence(); // "tea, coffee and cake"
</code-snippet>
@endverbatim
