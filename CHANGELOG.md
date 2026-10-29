# Changelog

All notable changes to `mixable` will be documented in this file.

## Unreleased

- The package is now `adrianspacely/mixable`, with the `AdrianSpacely\Mixable` namespace.
- Supports Laravel 11, 12, and 13 on PHP 8.2 or later.
- A Mixable macro creates the subclass without its constructor, copies the parent's state in, and copies it back out, returning the parent when the method returns `$this`.
- Mixin and Mixable macros can be called statically.
- `mixed($object)` mixes into any object, including classes that aren't `Macroable`.
- `ref($value)` passes an argument to a macro by reference.
- `ref()` and `mixed()` are functions in the `AdrianSpacely\Mixable` namespace, so import them with `use function`.
- Macro calls cache their reflection and trait lookups, which makes Mixable macros about 2.5 times faster.
- A PHPStan extension describes macros and `mixed()` objects with the types of the methods they call, and reports by-reference arguments passed without `ref()`.
- Mixins can reach private members of the class they were mixed into when they are called on a subclass.
- `mix()` no longer registers the package's own trait methods, like `__get()` and `bootMixable()`, as macros.

## 1.0.0 - 202X-XX-XX

- initial release
