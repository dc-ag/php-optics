<?php
declare(strict_types=1);

namespace dcAG\phpOptics;

trait CanValidateCallableSignature
{
    /**
     * @param callable $callable
     * @param Type|class-string $expectedReturnType
     * @return bool
     * @throws \ReflectionException
     */
    protected function callableHasExpectedReturnType(callable $callable, Type|string $expectedReturnType): bool
    {
        $reflection = new \ReflectionFunction($callable);
        $returnType = $reflection->getReturnType();
        if ($returnType === null) {
            return false;
        }
        $returnTypeName = $returnType->getName();
        if ($expectedReturnType instanceof Type) {
            return $expectedReturnType->value === $returnTypeName;
        }
        return $expectedReturnType === $returnTypeName;
    }

    /**
     * @param callable $callable
     * @param Type|class-string ...$expectedParamterTypes
     * @return bool
     * @throws \ReflectionException
     */
    protected function callableHasExpectedParameterTypes(callable $callable, Type|string ...$expectedParamterTypes): bool
    {
        $reflection = new \ReflectionFunction($callable);
        $parameters = $reflection->getParameters();
        if (count($parameters) !== count($expectedParamterTypes)) {
            return false;
        }
        foreach ($parameters as $index => $parameter) {
            $parameterType = $parameter->getType();
            if ($parameterType === null) {
                return false;
            }
            $parameterTypeName = $parameterType->getName();
            $expectedParameterType = $expectedParamterTypes[$index];
            if ($expectedParameterType instanceof Type) {
                if ($expectedParameterType->value !== $parameterTypeName) {
                    return false;
                }
            } else if ($expectedParameterType !== $parameterTypeName) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable $callable
     * @param Type|class-string $expectedReturnType
     * @param Type|class-string ...$expectedParamterTypes
     * @return bool
     * @throws \ReflectionException
     */
    protected function callableHasExpectedSignature(
        callable $callable,
        Type|string $expectedReturnType,
        Type|string ...$expectedParamterTypes
    ): bool {
        return
            $this->callableHasExpectedReturnType($callable, $expectedReturnType) &&
            $this->callableHasExpectedParameterTypes($callable, ...$expectedParamterTypes);
    }
}