<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixin;

class WalletMixin
{
    use Mixin;

    public string $macroable = Wallet::class;

    public function withdraw(int $amount): static
    {
        $this->balance -= $amount;

        return $this;
    }

    public function label(): string
    {
        return "{$this->owner}: {$this->balance}";
    }

    public function addBalanceTo(int &$total): void
    {
        $total += $this->balance;
    }
}
