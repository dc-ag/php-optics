<?php
declare(strict_types=1);

namespace dcAG\phpOptics;

trait CanCreatedTypedFunctionsFromUntypedFunctions
{
    protected function createdTypedFunctionFromTemplate(Type|string $returnType, callable $abstractedCallable, Type|string ...$parameterTypes): callable
    {
        $parameterString = "";
        $callableCallString = "";
        $parameterTypeHints = \array_map(
            static fn(Type|string $type): string => $type instanceof Type ? $type->value : $type,
            $parameterTypes
        );
        $parameterTypeCount = \count($parameterTypes);
        for($i = 0; $i < $parameterTypeCount; $i++) {
            if ($i > 0) {
                $parameterString .= ", ";
                $callableCallString .= ", ";
            }
            $parameterString .= $parameterTypeHints[$i] . ' $p' . $i;
            $callableCallString .= '$p' . $i;
        }
        $returnTypeString = $returnType instanceof Type ? $returnType->value : $returnType;

        $functionString = 'return fn(' . $parameterString . '): ' . $returnTypeString . ' => $abstractedCallable(' . $callableCallString . ');';
        $function = eval($functionString);
        return $function;
    }
}