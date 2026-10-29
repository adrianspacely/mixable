<?php

// Registers the mixins like a service provider would, so PHPStan can see their macros.

use AdrianSpacely\Mixable\Tests\Support\TypedCounterMixable;
use AdrianSpacely\Mixable\Tests\Support\TypedCounterMixin;

TypedCounterMixin::mix();
TypedCounterMixable::mix();
\AdrianSpacely\Mixable\Tests\Support\WalletMixin::mix();
\AdrianSpacely\Mixable\Tests\Support\WalletMixable::mix();
