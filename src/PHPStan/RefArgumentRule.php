<?php

namespace AdrianSpacely\Mixable\PHPStan;

use AdrianSpacely\Mixable\Ref;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * Reports macro calls which pass a by-reference parameter's argument without `ref()`,
 * because PHP passes arguments to `__call()` and `__callStatic()` by value.
 *
 * @implements Rule<Node\Expr>
 */
class RefArgumentRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider, private Macros $macros)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! ($node instanceof MethodCall || $node instanceof StaticCall) || ! $node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        $errors = [];

        foreach ($this->calledOn($node, $scope) as $classReflection) {
            $calledMethod = $this->macros->calledMethod($classReflection, $methodName);

            if ($calledMethod === null) {
                continue;
            }

            $parameters = $calledMethod->getOnlyVariant()->getParameters();

            foreach ($node->getArgs() as $position => $arg) {
                if ($arg->unpack) {
                    break;
                }

                $index = $arg->name === null ? $position : $this->indexOf($parameters, $arg->name->toString());
                $parameter = $index === null ? null : ($parameters[$index] ?? null);

                if ($parameter === null || $parameter->passedByReference()->no()) {
                    continue;
                }

                if ((new ObjectType(Ref::class))->isSuperTypeOf($scope->getType($arg->value))->yes()) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Parameter #%d $%s of method %s::%s() is passed by reference, but macros receive arguments by value. Wrap the argument with ref().',
                    $index + 1,
                    $parameter->getName(),
                    $node instanceof MethodCall
                        ? $scope->getType($node->var)->describe(VerbosityLevel::typeOnly())
                        : $classReflection->getDisplayName(),
                    $methodName,
                ))
                    ->identifier('mixable.refArgument')
                    ->line($arg->getStartLine())
                    ->build();
            }

            break;
        }

        return $errors;
    }

    /**
     * @return list<ClassReflection>
     */
    private function calledOn(MethodCall|StaticCall $node, Scope $scope): array
    {
        if ($node instanceof MethodCall) {
            return $scope->getType($node->var)->getObjectClassReflections();
        }

        if (! $node->class instanceof Name) {
            return $scope->getType($node->class)->getObjectClassReflections();
        }

        $className = $scope->resolveName($node->class);

        return $this->reflectionProvider->hasClass($className) ? [$this->reflectionProvider->getClass($className)] : [];
    }

    /**
     * @param list<\PHPStan\Reflection\ParameterReflection> $parameters
     */
    private function indexOf(array $parameters, string $name): ?int
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getName() === $name) {
                return $index;
            }
        }

        return null;
    }
}
