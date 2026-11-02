# Working on Mixable

This file is for agents working **on** this package. Agents using it in an app get `resources/boost/` through Laravel Boost instead.

## Commands

Run all three before calling a change done:

```bash
composer test
composer analyse
php-cs-fixer fix --dry-run --diff --config=.php_cs.dist.php --allow-risky=yes
```

This package uses php-cs-fixer, not Pint. Drop `--dry-run --diff` to apply fixes.

## Layout

- `src/Mixer.php` registers mixins as macros, records them in `Mixer::mixins()`, and runs mixin methods for macros and `Mixture`.
- `src/Mixin.php` and `src/Mixable.php` are the two traits. `src/Mixture.php` is the `mixed()` proxy, and `src/Ref.php` holds `ref()` arguments.
- `src/helpers.php` defines the namespaced `ref()` and `mixed()` functions.
- `src/PHPStan/` is the PHPStan extension, registered in `extension.neon`.
- `resources/boost/` is the Laravel Boost guideline and skill.
- `tests/Caveat*Test.php` document known limits as "problem" and "solution" tests.
- `tests/PHPStan/data/*.php` are PHPStan expectations: a line ending in `// type:` or `// error:` states what PHPStan should report there. `tests/PHPStan/analysed/` lets PHPStan analyse the traits.

## Conventions

- Write the test first for any behaviour change, and see it fail before changing `src/`.
- Methods users override, like `fromMacroable()`, `toMacroable()`, `inScope()`, and `bootMixable()`, take PHPDoc types rather than native ones, so existing overrides keep working.
- Macro calls are on the hot path. Cache anything reflection or trait lookups produce in `Mixer`.
- Keep `README.md`, `resources/boost/`, and `CHANGELOG.md` in step with behaviour changes. `tests/BoostResourcesTest.php` checks the Boost examples parse and only name things that exist.
- Ask before committing or pushing.
