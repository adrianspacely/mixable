<?php

namespace AdrianBrown\Mixable\Tests\Support;

use AdrianBrown\Mixable\Mixable;

class WalletMixable extends Wallet
{
    use Mixable;

    public function double(): static
    {
        return $this->deposit($this->getBalance());
    }
}
