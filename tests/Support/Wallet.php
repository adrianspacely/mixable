<?php

namespace AdrianSpacely\Mixable\Tests\Support;

class Wallet implements HasBalance
{
    public $owner = 'Grace';

    private int $balance;

    public function __construct(int $balance = 0)
    {
        $this->balance = $balance;
    }

    public function deposit(int $amount): static
    {
        $this->balance += $amount;

        return $this;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function depositInto(int &$total): void
    {
        $total += $this->balance;
    }
}
