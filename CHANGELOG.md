# Changelog

All notable changes to `mixable` will be documented in this file.

## Unreleased

### Breaking changes

- Requires Laravel 12 or 13, on PHP 8.2 or later. Laravel 11 and older aren't supported, because every Laravel 11 release has security advisories that won't be fixed, and Composer refuses to install them.
- `newMixableInstance()` is now `fromMacroable()`, and `newMacroableInstance()` is now `toMacroable()`, on both the `Mixin` and `Mixable` traits. They are the "in" and "out" steps of every macro call. If you override them, rename your overrides, because methods with the old names are no longer called.
- `mix()` throws an `InvalidArgumentException` when a Mixin or Mixable method has the same name as a method of the class it mixes into, including protected and private methods and methods a Mixable overrides. Those macros were silently never called, or only called from outside the class.
- Mixin and Mixable macros throw a `BadMethodCallException` when they're called on a class above the one they were mixed into. Laravel shares macros with every class that uses the same `Macroable` trait, so a Mixable on `Eloquent\Collection` used to work on every `collect()` collection too.

### Changes

- The package is now `adrianspacely/mixable`, with the `AdrianSpacely\Mixable` namespace.
- A Mixable macro creates the subclass without its constructor, copies the parent's state in, and copies it back out, returning the parent when the method returns `$this`.
- When a Mixable macro throws, the subclass's state is still copied back to the parent before the exception is rethrown, so changes made before it are kept.
- Mixin and Mixable macros can be called statically.
- `mixed($object)` mixes into any object, including classes that aren't `Macroable`.
- `ref($value)` passes an argument to a macro by reference.
- `ref()` and `mixed()` are functions in the `AdrianSpacely\Mixable` namespace, so import them with `use function`.
- Macro calls cache their reflection and trait lookups, which makes Mixable macros about 2.5 times faster.
- A Laravel Boost guideline and `mixable-development` skill help AI agents use the package.
- A PHPStan extension describes macros and `mixed()` objects with the types of the methods they call, and reports by-reference arguments passed without `ref()`.
- Mixins can reach private members of the class they were mixed into when they are called on a subclass.
- `mix()` no longer registers the package's own trait methods, like `__get()` and `bootMixable()`, as macros.

## 1.0.0 - 202X-XX-XX

- initial release
