<?php

namespace AdrianSpacely\Mixable\Tests\Support;

class BaseCounter
{
    private $privateBaseCount = 0;

    public function baseCount()
    {
        return $this->privateBaseCount;
    }

    protected function bumpBase(): void
    {
        $this->privateBaseCount++;
    }
}
