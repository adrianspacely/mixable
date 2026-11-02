<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixable;

class CallStaticMacroableMixable extends CallStaticMacroable
{
    use Mixable;

    public function tag(string $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function tagCount(): int
    {
        return count($this->tags);
    }
}
