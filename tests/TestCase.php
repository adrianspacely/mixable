<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixer;
use AdrianBrown\Mixable\Tests\Support\Counter;
use AdrianBrown\Mixable\Tests\Support\TestMe;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->beKindAndRewind(function () {
            TestMe::reset();
            Mixer::forgetMixins();
            Counter::flushMacros();
            Request::flushMacros();
            Collection::flushMacros();
            EloquentCollection::flushMacros();
        });
    }

    private function beKindAndRewind(Closure $callback): void
    {
        $this->beforeApplicationDestroyed($callback);
    }
}
