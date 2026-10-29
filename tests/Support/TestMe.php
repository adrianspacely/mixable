<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class TestMe
{
    use Macroable;

    public string $uuid;
    public $publicAttribute;
    protected $protectedAttribute;
    private $privateAttribute;
    public static $publicStaticAttribute = 'public_static_attribute_value';
    protected static $protectedStaticAttribute = 'protected_static_attribute_value';
    private static $privateStaticAttribute = 'private_static_attribute_value';

    public function __construct($publicAttribute = null, $protectedAttribute = null, $privateAttribute = null)
    {
        $this->uuid = Str::uuid();
        $this->publicAttribute = $publicAttribute;
        $this->protectedAttribute = $protectedAttribute;
        $this->privateAttribute = $privateAttribute;
    }

    public static function new(...$args): self
    {
        return new static(...$args);
    }

    public static function reset(): void
    {
        static::flushMacros();
        static::$publicStaticAttribute = 'public_static_attribute_value';
        static::$protectedStaticAttribute = 'protected_static_attribute_value';
        static::$privateStaticAttribute = 'private_static_attribute_value';
    }

    public function publicCallReturningThis()
    {
        return tap($this, function ($testMe) {
            test()->expect($this)->toBe($testMe);
            test()->expect($this->uuid)->toBe($testMe->uuid);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    public function publicCallReturningNewInstance()
    {
        return tap(new static(), function ($testMe) {
            test()->expect($this)->not->toBe($testMe);
            test()->expect($this->uuid)->not->toBe($testMe->uuid);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    public function publicCallReturningValue()
    {
        return 'public_call_returning_value';
    }

    public function publicCallReturningVoid(): void
    {
    }

    public function publicCallReturningNull()
    {
        return null;
    }

    protected function protectedCallReturningThis()
    {
        return tap($this, function ($testMe) {
            test()->expect($this)->toBe($testMe);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    protected function protectedCallReturningNewInstance()
    {
        return tap(new static(), function ($testMe) {
            test()->expect($this)->not->toBe($testMe);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    protected function protectedCallReturningValue()
    {
        return 'protected_call_returning_value';
    }

    protected function protectedCallReturningVoid(): void
    {
    }

    protected function protectedCallReturningNull()
    {
        return null;
    }

    private function privateCallReturningThis()
    {
        return tap($this, function ($testMe) {
            test()->expect($this)->toBe($testMe);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    private function privateCallReturningNewInstance()
    {
        return tap(new static(), function ($testMe) {
            test()->expect($this)->not->toBe($testMe);
            test()->expect($testMe)->toBeInstanceOf(TestMe::class);
        });
    }

    private function privateCallReturningValue()
    {
        return 'private_call_returning_value';
    }

    private function privateCallReturningVoid(): void
    {
    }

    private function privateCallReturningNull()
    {
        return null;
    }

    public static function publicStaticCallReturningNewInstance()
    {
        return new static();
    }

    public static function publicStaticCallReturningValue()
    {
        return 'public_static_call_result';
    }

    public static function publicStaticCallReturningVoid(): void
    {
    }

    public static function publicStaticCallReturningNull()
    {
        return null;
    }

    protected static function protectedStaticCallReturningNewInstance()
    {
        return new static();
    }

    protected static function protectedStaticCallReturningValue()
    {
        return 'protected_static_call_result';
    }

    protected static function protectedStaticCallReturningVoid(): void
    {
    }

    protected static function protectedStaticCallReturningNull()
    {
        return null;
    }

    private static function privateStaticCallReturningNewInstance()
    {
        return new static();
    }

    private static function privateStaticCallReturningValue()
    {
        return 'private_static_call_result';
    }

    private static function privateStaticCallReturningVoid(): void
    {
    }

    private static function privateStaticCallReturningNull()
    {
        return null;
    }
}
