<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixer;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Tests\Support\CollectionMixin;
use AdrianBrown\Mixable\Tests\Support\Counter;
use AdrianBrown\Mixable\Tests\Support\SpecialCounter;
use AdrianBrown\Mixable\Tests\Support\TestMe;
use AdrianBrown\Mixable\Tests\Support\TransposeCollectionMethod;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LogicException;

it('works', function () {
    CollectionMixin::mix(Collection::class);

    $collection = Collection::make([
        [0, 1],
        [0, 1],
        [0, 1],
    ]);

    expect($collection)->toBeInstanceOf(Collection::class);

    $transposed = $collection->transpose();

    expect($transposed)->toBeInstanceOf(Collection::class);

    expect($transposed->toArray())->toBe([
        [0, 0, 0],
        [1, 1, 1],
    ]);
});

it('does not add the macros to the macroable/s if `mix()` is not called', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;
    };
    expect(Collection::hasMacro('transpose'))->toBe(false);

    $mixin->mix(Collection::class);
    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('records which mixin each macro came from', function () {
    CollectionMixin::mix([Collection::class, Request::class]);

    expect(Mixer::mixins())->toBe([
        Collection::class => ['transpose' => CollectionMixin::class],
        Request::class => ['transpose' => CollectionMixin::class],
    ]);
    expect(Collection::hasMacro('__get'))->toBe(false);
});

it('adds the macro to the macroable via `mix(string)`', function () {
    CollectionMixin::mix(Collection::class);

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable/s via `mix(array)`', function () {
    CollectionMixin::mix([
        Collection::class,
    ]);

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable via `mix()` where the mixin has a `public string $macroable;` class property', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;

        public $macroable = Collection::class;
    };

    $mixin->mix();

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable/s via `mix()` where the mixin has a `public array $macroable;` class property', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;

        public $macroable = [Collection::class];
    };

    $mixin->mix();

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable via `mix()` where the mixin has a `public string $macroables;` class property', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;

        public $macroables = Collection::class;
    };

    $mixin->mix();

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable/s via `mix()` where the mixin has a `public array $macroables;` class property', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;

        public $macroables = [Collection::class];
    };

    $mixin->mix();

    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('adds the macro to the macroable/s via passing properties into `mix(...)` function and using the `$macroable` class properties', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;

        public $macroable = Collection::class;
    };

    $mixin->mix(Request::class);

    expect(Request::hasMacro('transpose'))->toBe(true);
    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('does not add the macros to the macroable if `mix()` is not called', function () {
    $mixin = new class () {
        use Mixin;
        use TransposeCollectionMethod;
    };
    expect(Collection::hasMacro('transpose'))->toBe(false);

    $mixin->mix(Collection::class);
    expect(Collection::hasMacro('transpose'))->toBe(true);
});

it('will affect the macroable instance when making method calls', function () {
    $mixin = new class () {
        use Mixin;

        public function mixinPush($item)
        {
            $test = $this->push($item);
            $callToProtectedMethod = $test->sortByMany();

            // Because it returned $this (collection $this), but our forwarded call retutned the decorator (mixin $this)
            expect($test)->toBeInstanceOf(get_class($this));

            // However, if it returned static::make() the decorator is not returned because it is not the same instance as the macroable.
            expect($callToProtectedMethod)->toBeInstanceOf(Collection::class);

            return $this;
        }
    };

    $mixin->mix(Collection::class);

    $collection = Collection::make(['a']);
    $resultA = $collection->mixinPush('2');
    $resultB = $collection->push('d');

    // Even though we return the decorator, the macro will detect this and return the macroable instance for userland.
    expect($resultA)->toBeInstanceOf(Collection::class);
    expect($resultB)->toBeInstanceOf(Collection::class);
    expect($resultA)->not->toBeInstanceOf(get_class($mixin));
    expect($resultB)->not->toBeInstanceOf(get_class($mixin));
    expect($collection->toArray())->toBe(['a', '2', 'd']);
});

it('will forward static calls to the macroable', function () {
    $mixin = new class () {
        use Mixin;

        public function example()
        {
            return static::make(['steamed', 'hams']);
        }
    };
    $mixin->mix(Collection::class);
    $collection = Collection::make(['foo', 'bar']);

    $result = $collection->example();

    expect($collection)->not->toBe($result);
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->toArray())->toBe(['steamed', 'hams']);
});

it('can get attributes from the macroable', function () {
    $mixin = new class () {
        use Mixin;

        public function example()
        {
            return $this->items;
        }
    };
    $mixin->mix(Collection::class);
    $collection = Collection::make(['foo', 'bar']);

    $result = $collection->example();

    expect($result)->toBeArray();
    expect($result)->toBe(['foo', 'bar']);
    expect($collection->all())->toBe(['foo', 'bar']);
    expect($collection->push('baz')->example())->toBe(['foo', 'bar', 'baz']);
    expect($collection->all())->toBe(['foo', 'bar', 'baz']);
});

it('can set attributes on the macroable', function () {
    $mixin = new class () {
        use Mixin;

        public function example()
        {
            $this->items = ['fizz', 'bang'];

            return $this;
        }
    };
    $mixin->mix(Collection::class);
    $collection = Collection::make(['foo', 'bar']);

    $result = $collection->example();

    expect($result)->toBe($collection);
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->toArray())->toBe(['fizz', 'bang']);
    expect($collection->toArray())->toBe(['fizz', 'bang']);
});

it('will macro public methods', function () {
    $mixin = new class () {
        use Mixin;

        public function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(true);
});

it('does not macro protected methods', function () {
    $mixin = new class () {
        use Mixin;

        protected function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(false);
});

it('does not macro private methods', function () {
    $mixin = new class () {
        use Mixin;

        private function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(false);
});

it('does not macro static public methods', function () {
    $mixin = new class () {
        use Mixin;

        public static function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(false);
});

it('does not macro static protected methods', function () {
    $mixin = new class () {
        use Mixin;

        protected static function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(false);
});

it('does not macro static private methods', function () {
    $mixin = new class () {
        use Mixin;

        private static function example()
        {
            return $this;
        }
    };

    $mixin->mix(Collection::class);

    expect(Collection::hasMacro('example'))->toBe(false);
});

it('cannot get static attributes on the macroable via the mixin class', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function staticallyFromMixinUsingTheStaticKeyword()
        {
            return static::$publicStaticAttribute;
        }

        public function staticallyFromMixinUsingTheMacroableClassName()
        {
            return TestMe::$publicStaticAttribute;
        }
    })->mix();

    expect(TestMe::hasMacro('staticallyFromMixinUsingTheStaticKeyword'))->toBe(true);
    expect(TestMe::hasMacro('staticallyFromMixinUsingTheMacroableClassName'))->toBe(true);
    expect(TestMe::new()->staticallyFromMixinUsingTheMacroableClassName())->toBe('public_static_attribute_value');
    TestMe::new()->staticallyFromMixinUsingTheStaticKeyword(); // This will throw an exception.
})->throws('Access to undeclared static property');

it('cannot set static attributes on the macroable via the mixin class', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function staticallyFromMixinUsingTheStaticKeyword()
        {
            static::$publicStaticAttribute = 'bar';
        }

        public function staticallyFromMixinUsingTheMacroableClassName()
        {
            TestMe::$publicStaticAttribute = 'foo';
        }
    })->mix();

    expect(TestMe::hasMacro('staticallyFromMixinUsingTheStaticKeyword'))->toBe(true);
    expect(TestMe::hasMacro('staticallyFromMixinUsingTheMacroableClassName'))->toBe(true);

    TestMe::new()->staticallyFromMixinUsingTheMacroableClassName();
    expect(TestMe::$publicStaticAttribute)->toBe('foo');
    TestMe::new()->staticallyFromMixinUsingTheStaticKeyword(); // This will throw an exception.
})->throws('Access to undeclared static property');

it('can get static attributes on the macroable via the mixin class if it is within an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function staticallyUsingTheStaticKeywordWithinAnInScopeCallback()
        {
            // The closure is scoped to the macroable which is why this works.
            return $this->inScope(fn () => static::$publicStaticAttribute);
        }
    })->mix();

    expect(TestMe::hasMacro('staticallyUsingTheStaticKeywordWithinAnInScopeCallback'))->toBe(true);
    expect(TestMe::new()->staticallyUsingTheStaticKeywordWithinAnInScopeCallback())->toBe('public_static_attribute_value');
});

it('can set static attributes on the macroable via the mixin class if it is within an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function staticallyFromMixinUsingTheStaticKeyword()
        {
            $this->inScope(fn () => static::$publicStaticAttribute = 'bar');
        }

        public function staticallyFromMixinUsingTheMacroableClassName()
        {
            TestMe::$publicStaticAttribute = 'foo';
        }
    })->mix();

    expect(TestMe::hasMacro('staticallyFromMixinUsingTheStaticKeyword'))->toBe(true);
    expect(TestMe::hasMacro('staticallyFromMixinUsingTheMacroableClassName'))->toBe(true);

    TestMe::new()->staticallyFromMixinUsingTheMacroableClassName();
    expect(TestMe::$publicStaticAttribute)->toBe('foo');
    TestMe::new()->staticallyFromMixinUsingTheStaticKeyword();
    expect(TestMe::$publicStaticAttribute)->toBe('bar');
});

it('can return a value that is not the macroable or the mixin', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function example()
        {
            return 'hello';
        }
    })->mix();
    expect(TestMe::hasMacro('example'))->toBe(true);

    $result = TestMe::new()->example();

    expect($result)->toBe('hello');
});

it('returns the mixin instance when forwarding calls if the macroable returned $this', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function example()
        {
            /** @see \AdrianBrown\Mixable\Tests\Support\TestMe@publicCallReturningThis for the assertions that the return value is TestMe */
            $value = $this->publicCallReturningThis();

            // Because the Mixin trait forwards calls to the macroable, the return value is switched to the mixin instance if the value is the macroable instance.
            expect($value)->toBeInstanceOf(static::class);
            expect(static::class)->not->toBe(TestMe::class);
            expect($value)->not->toBeInstanceOf(TestMe::class);
            expect($this->uuid)->toBe($value->uuid);

            // Therefore, we are returning the mixin instance.
            return $value;
        }
    })->mix();
    expect(TestMe::hasMacro('example'))->toBe(true);
    $testMe = TestMe::new();

    $result = $testMe->example();

    // However, the macro switches a returned mixin instance to the macroable instance it decorates.
    expect($result)->toBe($testMe); // same instance
    expect($result)->toBeInstanceOf(TestMe::class);
});

it('will return the macroable object if it is not the same instance as the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function example()
        {
            /** @see \AdrianBrown\Mixable\Tests\Support\TestMe@publicCallReturningNewInstance for the assertions that the return value is a different TestMe instance */
            $value = $this->publicCallReturningNewInstance();

            // The Mixin trait will not switch the TestMe instance with the Mixin because the TestMe instance is not the instance that the mixin was invoked from.
            expect(static::class)->not->toBe(TestMe::class);
            expect($value)->not->toBeInstanceOf(static::class);
            expect($value)->toBeInstanceOf(TestMe::class);
            expect($this->uuid)->not->toBe($value->uuid);

            // Therefore, we are returning a different TestMe instance.
            return $value;
        }
    })->mix();
    expect(TestMe::hasMacro('example'))->toBe(true);
    $testMe = TestMe::new();

    $result = $testMe->example();

    // However, the macro will not switch to the macroable instance it decorates, this is a different TestMe instance.
    expect($result)->not->toBe($testMe); // different instance
    expect($testMe)->toBeInstanceOf(TestMe::class);
    expect($result)->toBeInstanceOf(TestMe::class);
});

test('the mixin can forward calls to public methods on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinForwardingToPublicCallReturningThis()
        {
            return $this->publicCallReturningThis();
        }

        public function mixinForwardingToPublicCallReturningNewInstance()
        {
            return $this->publicCallReturningNewInstance();
        }

        public function mixinForwardingToPublicCallReturningValue()
        {
            return $this->publicCallReturningValue();
        }

        public function mixinForwardingToPublicCallReturningVoid()
        {
            return $this->publicCallReturningVoid();
        }

        public function mixinForwardingToPublicCallReturningNull()
        {
            return $this->publicCallReturningNull();
        }
    })->mix();
    expect(TestMe::hasMacro('mixinForwardingToPublicCallReturningThis'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPublicCallReturningNewInstance'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPublicCallReturningValue'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPublicCallReturningVoid'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPublicCallReturningNull'))->toBe(true);
    $testMe = TestMe::new();

    tap($testMe->mixinForwardingToPublicCallReturningThis(), function ($result) use ($testMe) {
        expect($result)->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
        expect($testMe->uuid)->toBe($result->uuid);
    });

    tap($testMe->mixinForwardingToPublicCallReturningNewInstance(), function ($result) use ($testMe) {
        expect($result)->not->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
        expect($testMe->uuid)->not->toBe($result->uuid);
    });

    tap($testMe->mixinForwardingToPublicCallReturningValue(), function ($result) {
        expect($result)->toBe('public_call_returning_value');
    });

    tap($testMe->mixinForwardingToPublicCallReturningVoid(), function ($result) {
        expect($result)->toBe(null);
    });

    tap($testMe->mixinForwardingToPublicCallReturningNull(), function ($result) {
        expect($result)->toBe(null);
    });
});

test('the mixin can forward calls to protected methods on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinForwardingToProtectedCallReturningThis()
        {
            return $this->protectedCallReturningThis();
        }

        public function mixinForwardingToProtectedCallReturningNewInstance()
        {
            return $this->protectedCallReturningNewInstance();
        }

        public function mixinForwardingToProtectedCallReturningValue()
        {
            return $this->protectedCallReturningValue();
        }

        public function mixinForwardingToProtectedCallReturningVoid()
        {
            return $this->protectedCallReturningVoid();
        }

        public function mixinForwardingToProtectedCallReturningNull()
        {
            return $this->protectedCallReturningNull();
        }
    })->mix();
    expect(TestMe::hasMacro('mixinForwardingToProtectedCallReturningThis'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToProtectedCallReturningNewInstance'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToProtectedCallReturningValue'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToProtectedCallReturningVoid'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToProtectedCallReturningNull'))->toBe(true);
    $testMe = TestMe::new();

    tap($testMe->mixinForwardingToProtectedCallReturningThis(), function ($result) use ($testMe) {
        expect($result)->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
    });

    tap($testMe->mixinForwardingToProtectedCallReturningNewInstance(), function ($result) use ($testMe) {
        expect($result)->not->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
    });

    tap($testMe->mixinForwardingToProtectedCallReturningValue(), function ($result) {
        expect($result)->toBe('protected_call_returning_value');
    });

    tap($testMe->mixinForwardingToProtectedCallReturningVoid(), function ($result) {
        expect($result)->toBe(null);
    });

    tap($testMe->mixinForwardingToProtectedCallReturningNull(), function ($result) {
        expect($result)->toBe(null);
    });
});

test('the mixin can forward calls to private methods on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinForwardingToPrivateCallReturningThis()
        {
            return $this->privateCallReturningThis();
        }

        public function mixinForwardingToPrivateCallReturningNewInstance()
        {
            return $this->privateCallReturningNewInstance();
        }

        public function mixinForwardingToPrivateCallReturningValue()
        {
            return $this->privateCallReturningValue();
        }

        public function mixinForwardingToPrivateCallReturningVoid()
        {
            return $this->privateCallReturningVoid();
        }

        public function mixinForwardingToPrivateCallReturningNull()
        {
            return $this->privateCallReturningNull();
        }
    })->mix();
    expect(TestMe::hasMacro('mixinForwardingToPrivateCallReturningThis'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPrivateCallReturningNewInstance'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPrivateCallReturningValue'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPrivateCallReturningVoid'))->toBe(true);
    expect(TestMe::hasMacro('mixinForwardingToPrivateCallReturningNull'))->toBe(true);
    $testMe = TestMe::new();

    tap($testMe->mixinForwardingToPrivateCallReturningThis(), function ($result) use ($testMe) {
        expect($result)->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
    });

    tap($testMe->mixinForwardingToPrivateCallReturningNewInstance(), function ($result) use ($testMe) {
        expect($result)->not->toBe($testMe);
        expect($result)->toBeInstanceOf(TestMe::class);
    });

    tap($testMe->mixinForwardingToPrivateCallReturningValue(), function ($result) {
        expect($result)->toBe('private_call_returning_value');
    });

    tap($testMe->mixinForwardingToPrivateCallReturningVoid(), function ($result) {
        expect($result)->toBe(null);
    });

    tap($testMe->mixinForwardingToPrivateCallReturningNull(), function ($result) {
        expect($result)->toBe(null);
    });
});

test('the mixin can get public attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPublicAttribute()
        {
            return $this->publicAttribute;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPublicAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');

    tap($testMe->mixinGetPublicAttribute(), function ($result) {
        expect($result)->toBe('public_value');
        expect($result)->not->toBe('protected_value');
        expect($result)->not->toBe('private_value');
    });
});

test('the mixin can get protected attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetProtectedAttribute()
        {
            return $this->protectedAttribute;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetProtectedAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');

    tap($testMe->mixinGetProtectedAttribute(), function ($result) {
        expect($result)->not->toBe('public_value');
        expect($result)->toBe('protected_value');
        expect($result)->not->toBe('private_value');
    });
});

test('the mixin can get private attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPrivateAttribute()
        {
            return $this->privateAttribute;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPrivateAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');

    tap($testMe->mixinGetPrivateAttribute(), function ($result) {
        expect($result)->not->toBe('public_value');
        expect($result)->not->toBe('protected_value');
        expect($result)->toBe('private_value');
    });
});

test('the mixin can get private attributes declared on the macroable when called on a subclass', function () {
    (new class () {
        use Mixin;

        public string $macroable = Counter::class;

        public function mixinGetPrivateCount()
        {
            return $this->privateCount;
        }
    })->mix();

    expect((new SpecialCounter(5))->mixinGetPrivateCount())->toBe(5);
});

test('the mixin can set public attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPublicAttribute()
        {
            return $this->publicAttribute;
        }

        public function mixinSetPublicAttribute($value)
        {
            $this->publicAttribute = $value;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPublicAttribute'))->toBe(true);
    expect(TestMe::hasMacro('mixinSetPublicAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');
    expect($testMe->mixinGetPublicAttribute())->toBe('public_value');

    $testMe->mixinSetPublicAttribute('new_public_value');

    expect($testMe->mixinGetPublicAttribute())->toBe('new_public_value');
});

test('the mixin can set protected attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetProtectedAttribute()
        {
            return $this->protectedAttribute;
        }

        public function mixinSetProtectedAttribute($value)
        {
            $this->protectedAttribute = $value;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetProtectedAttribute'))->toBe(true);
    expect(TestMe::hasMacro('mixinSetProtectedAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');
    expect($testMe->mixinGetProtectedAttribute())->toBe('protected_value');

    $testMe->mixinSetProtectedAttribute('new_protected_value');

    expect($testMe->mixinGetProtectedAttribute())->toBe('new_protected_value');
});

test('the mixin can set private attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPrivateAttribute()
        {
            return $this->privateAttribute;
        }

        public function mixinSetPrivateAttribute($value)
        {
            $this->privateAttribute = $value;
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPrivateAttribute'))->toBe(true);
    expect(TestMe::hasMacro('mixinSetPrivateAttribute'))->toBe(true);
    $testMe = TestMe::new('public_value', 'protected_value', 'private_value');
    expect($testMe->mixinGetPrivateAttribute())->toBe('private_value');

    $testMe->mixinSetPrivateAttribute('new_private_value');

    expect($testMe->mixinGetPrivateAttribute())->toBe('new_private_value');
});

test('the mixin can get public static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPublicStaticAttribute()
        {
            return $this->inScope(fn () => static::$publicStaticAttribute);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPublicStaticAttribute'))->toBe(true);

    tap(TestMe::new()->mixinGetPublicStaticAttribute(), function ($result) {
        expect($result)->toBe('public_static_attribute_value');
    });
});

test('the mixin can set public static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPublicStaticAttribute()
        {
            return $this->inScope(fn () => static::$publicStaticAttribute);
        }

        public function mixinSetPublicStaticAttribute($value)
        {
            $this->inScope(fn () => static::$publicStaticAttribute = $value);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPublicStaticAttribute'))->toBe(true);
    tap(TestMe::new()->mixinGetPublicStaticAttribute(), function ($result) {
        expect($result)->toBe('public_static_attribute_value');
    });

    TestMe::new()->mixinSetPublicStaticAttribute('new_public_static_attribute_value');

    tap(TestMe::new()->mixinGetPublicStaticAttribute(), function ($result) {
        expect($result)->toBe('new_public_static_attribute_value');
    });
});

test('the mixin can get protected static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetProtectedStaticAttribute()
        {
            return $this->inScope(fn () => static::$protectedStaticAttribute);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetProtectedStaticAttribute'))->toBe(true);

    tap(TestMe::new()->mixinGetProtectedStaticAttribute(), function ($result) {
        expect($result)->toBe('protected_static_attribute_value');
    });
});

test('the mixin can set protected static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetProtectedStaticAttribute()
        {
            return $this->inScope(fn () => static::$protectedStaticAttribute);
        }

        public function mixinSetProtectedStaticAttribute($value)
        {
            $this->inScope(fn () => static::$protectedStaticAttribute = $value);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetProtectedStaticAttribute'))->toBe(true);
    tap(TestMe::new()->mixinGetProtectedStaticAttribute(), function ($result) {
        expect($result)->toBe('protected_static_attribute_value');
    });

    TestMe::new()->mixinSetProtectedStaticAttribute('new_protected_static_attribute_value');

    tap(TestMe::new()->mixinGetProtectedStaticAttribute(), function ($result) {
        expect($result)->toBe('new_protected_static_attribute_value');
    });
});

test('the mixin can get private static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPrivateStaticAttribute()
        {
            return $this->inScope(fn () => static::$privateStaticAttribute);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPrivateStaticAttribute'))->toBe(true);

    tap(TestMe::new()->mixinGetPrivateStaticAttribute(), function ($result) {
        expect($result)->toBe('private_static_attribute_value');
    });
});

test('the mixin can set private static attributes on the macroable through an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function mixinGetPrivateStaticAttribute()
        {
            return $this->inScope(fn () => static::$privateStaticAttribute);
        }

        public function mixinSetPrivateStaticAttribute($value)
        {
            $this->inScope(fn () => static::$privateStaticAttribute = $value);
        }
    })->mix();
    expect(TestMe::hasMacro('mixinGetPrivateStaticAttribute'))->toBe(true);
    tap(TestMe::new()->mixinGetPrivateStaticAttribute(), function ($result) {
        expect($result)->toBe('private_static_attribute_value');
    });

    TestMe::new()->mixinSetPrivateStaticAttribute('new_private_static_attribute_value');

    tap(TestMe::new()->mixinGetPrivateStaticAttribute(), function ($result) {
        expect($result)->toBe('new_private_static_attribute_value');
    });
});

test('can call a macro statically', function () {
    ($mixin = new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function returnValue()
        {
            return 'value';
        }

        public function returnThis()
        {
            return $this;
        }
    })->mix();

    expect(TestMe::returnValue())->toBe('value');
    expect(TestMe::returnThis())->toBeInstanceOf(get_class($mixin));
});

test('a macro called statically forwards static calls to the macroable class', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function forwardStaticCall()
        {
            return static::publicStaticCallReturningValue();
        }

        public function forwardNonStaticCall()
        {
            return $this->publicCallReturningValue();
        }
    })->mix();

    expect(TestMe::forwardStaticCall())->toBe('public_static_call_result');
    expect(fn () => TestMe::forwardNonStaticCall())->toThrow(Error::class, 'cannot be called statically');
});

test('a macro called statically uses the macroable class scope within an `inScope` callback', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function getPrivateStaticAttribute()
        {
            return $this->inScope(fn () => static::$privateStaticAttribute);
        }
    })->mix();

    expect(TestMe::getPrivateStaticAttribute())->toBe('private_static_attribute_value');
});

test('a macro called statically cannot get or set attributes on the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function getAttribute()
        {
            return $this->publicAttribute;
        }

        public function setAttribute()
        {
            $this->publicAttribute = 'value';
        }
    })->mix();

    expect(fn () => TestMe::getAttribute())->toThrow(LogicException::class, 'Cannot get [publicAttribute] on [' . TestMe::class . '] because the macro was called statically.');
    expect(fn () => TestMe::setAttribute())->toThrow(LogicException::class, 'Cannot set [publicAttribute] on [' . TestMe::class . '] because the macro was called statically.');
});
