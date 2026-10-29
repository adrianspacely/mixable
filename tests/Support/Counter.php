<?php

namespace AdrianBrown\Mixable\Tests\Support;

use Illuminate\Support\Traits\Macroable;

class Counter extends BaseCounter
{
    use Macroable;

    public $publicCount = 0;
    protected $protectedCount = 0;
    private $privateCount = 0;
    public $constructed = false;

    public function __construct(int $start = 0)
    {
        $this->constructed = true;
        $this->publicCount = $start;
        $this->protectedCount = $start;
        $this->privateCount = $start;
    }

    public function counts(): array
    {
        return [$this->publicCount, $this->protectedCount, $this->privateCount, $this->baseCount()];
    }

    public function bump(): void
    {
        $this->publicCount++;
        $this->protectedCount++;
        $this->privateCount++;
        $this->bumpBase();
    }
}
