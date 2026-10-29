<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use Illuminate\Support\Traits\Macroable;

class Ticket
{
    use Macroable;

    public string $status = 'open';

    public function __construct(public readonly string $id)
    {
    }
}
