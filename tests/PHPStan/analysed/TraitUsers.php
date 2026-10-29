<?php

// PHPStan only analyses a trait through a class that uses it. These classes let it analyse the package's traits.

namespace AdrianBrown\Mixable\Tests\PHPStan\Analysed;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;

class MixinUser
{
    use Mixin;
}

class MacroableParent
{
    use \Illuminate\Support\Traits\Macroable;
}

class MixableUser extends MacroableParent
{
    use Mixable;
}
