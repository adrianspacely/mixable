<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixable;

class WalletMixable extends Wallet
{
    use Mixable;

    public function double(): static
    {
        return $this->deposit($this->getBalance());
    }
}
