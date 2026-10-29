<?php

// Registers the mixins like a service provider would, so PHPStan can see their macros.

use AdrianBrown\Mixable\Tests\Support\TypedCounterMixable;
use AdrianBrown\Mixable\Tests\Support\TypedCounterMixin;

TypedCounterMixin::mix();
TypedCounterMixable::mix();
\AdrianBrown\Mixable\Tests\Support\WalletMixin::mix();
\AdrianBrown\Mixable\Tests\Support\WalletMixable::mix();
