<?php

namespace AdrianBrown\Mixable\PHPStan;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports by-reference parameters on methods which `mix()` registers as macros,
 * because PHP passes arguments to `__call()` and `__callStatic()` by value.
 *
 * @implements Rule<InClassMethodNode>
 */
class ParameterByReferenceRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $method = $node->getMethodReflection();

        $trait = match (true) {
            $class->hasTraitUse(Mixin::class) => 'Mixin',
            $class->hasTraitUse(Mixable::class) => 'Mixable',
            default => null,
        };

        if ($trait === null || ! $method->isPublic() || $method->isStatic() || $method->isAbstract()->yes()) {
            return [];
        }

        $errors = [];

        foreach ($node->getOriginalNode()->getParams() as $index => $param) {
            if (! $param->byRef) {
                continue;
            }

            $name = $param->var instanceof Node\Expr\Variable && is_string($param->var->name) ? $param->var->name : '';

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Parameter #%d $%s of %s method %s::%s() is passed by reference, but macros receive arguments by value.',
                $index + 1,
                $name,
                $trait,
                $class->getDisplayName(),
                $method->getName(),
            ))
                ->identifier('mixable.parameterByReference')
                ->line($param->getStartLine())
                ->tip('Return the value instead, or pass an object.')
                ->build();
        }

        return $errors;
    }
}
