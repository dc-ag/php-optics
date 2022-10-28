<?php
declare(strict_types=1);

namespace dcAG\phpOptics;


use Closure;
use InvalidArgumentException;
use function get_debug_type;

/**
 * @template I
 * @template P
 */
class Lens
{
    use CanValidateCallableSignature;
    use CanCreatedTypedFunctionsFromUntypedFunctions;

    /**
     * @var class-string
     */
    public readonly string $fromTypeName;

    /**
     * @var Type|class-string<P>
     */
    public readonly Type|string $toType;

    public readonly Closure $getter;

    public readonly Closure $constructor;

    /**
     * @param class-string<I> $fromTypeName
     * @param Type|class-string<P> $toType
     * @param callable $getter must be from "fromType" to "toType"
     * @param callable $constructor must be from (fromType, toType) to fromType
     */
    public function __construct(
        string $fromTypeName,
        Type|string $toType,
        callable $getter,
        callable $constructor
    ) {
        //validate fromTypeName is valid class-string
        if (\class_exists($fromTypeName) === false && \interface_exists($fromTypeName) === false) {
            throw new InvalidArgumentException("fromTypeName must be a class-string or interface-string.");
        }
        //validate toType is Type or valid class-string
        if (\is_string($toType)) {
            if (\class_exists($toType) === false && \interface_exists($toType) === false) {
                throw new InvalidArgumentException("toType must be an instance of the Type-enum or a class-string or an interface-string.");
            }
        }
        //check if getter has correct signature
        $getterIsValid = $this->callableHasExpectedSignature($getter, $toType, $fromTypeName);
        if (!$getterIsValid) {
            throw new InvalidArgumentException("getter must be from \"fromType\" to \"toType\".");
        }
        //check if constructor has correct signature
        $constructorIsValid = $this->callableHasExpectedSignature($constructor, $fromTypeName, $fromTypeName, $toType);
        if (!$constructorIsValid) {
            throw new InvalidArgumentException("constructor must be from (\"fromType\", \"toType\") to \"fromType\".");
        }
        $this->fromTypeName = $fromTypeName;
        $this->toType = $toType;
        $this->getter = ($getter instanceof Closure) ? $getter : $getter(...);
        $this->constructor = ($constructor instanceof Closure) ? $constructor : $constructor(...);
    }

    /**
     * @param I $from
     * @return P
     */
    public function get(object $from): mixed
    {
        if (!($from instanceof $this->fromTypeName)) {
            throw new InvalidArgumentException("from must be an instance of \"fromTypeName\".");
        }
        return ($this->getter)($from);
    }

    /**
     * @param I $in
     * @param P $replacement
     * @return I
     */
    public function update(object $in, mixed $replacement): object
    {
        if (!($in instanceof $this->fromTypeName)) {
            throw new InvalidArgumentException("in must be an instance of \"fromTypeName\".");
        }
        if ($this->toType instanceof Type) {
            //$toTypeName = Type::gettypeEquivalent($this->toType);
            $actualType = get_debug_type($replacement);
            if ($this->toType->value !== $actualType) {
                throw new InvalidArgumentException("replacement must be of type [{$this->toType->value}] - got [$actualType].");
            }
        } else if (!($replacement instanceof $this->toType)) {
            throw new InvalidArgumentException("replacement must be an instance of \"toType\".");
        }
        return ($this->constructor)($in, $replacement);
    }

    public function compose(Lens $other): Lens
    {
        if ($this->toType !== $other->fromTypeName) {
            throw new InvalidArgumentException("toType of this lens must be equal to fromTypeName of other lens.");
        }

        $getterFn = fn($from) => $other->get($this->get($from));
        $constructorFN = fn($from, $replacement) => $this->update($from, $other->update($this->get($from), $replacement));

        $toTypeString = $other->toType instanceof Type ? $other->toType->value : $other->toType;

        $typedGetterFn = $this->createdTypedFunctionFromTemplate($toTypeString, $getterFn, $this->fromTypeName);
        $typedConstructorFn = $this->createdTypedFunctionFromTemplate($this->fromTypeName, $constructorFN, $this->fromTypeName, $toTypeString);

        return new Lens(
            $this->fromTypeName,
            $other->toType,
            $typedGetterFn,
            $typedConstructorFn
        );
    }

    public function zipWith(Lens $otherLens): Lens
    {
        if ($this->fromTypeName !== $otherLens->fromTypeName) {
            throw new InvalidArgumentException("fromTypeName of this lens must be equal to fromTypeName of other lens.");
        }

        $getterFn = fn($from) => [$this->get($from), $otherLens->get($from)];
        $constructorFn = fn($from, $replacement) => $otherLens->update($this->update($from, $replacement[0]), $replacement[1]);

        $newProjectionType = Type::ARRAY;

        $typedGetterFn = $this->createdTypedFunctionFromTemplate($newProjectionType, $getterFn, $this->fromTypeName);
        $typedConstructorFn = $this->createdTypedFunctionFromTemplate($this->fromTypeName, $constructorFn, $this->fromTypeName, $newProjectionType);

        return new Lens(
            $this->fromTypeName,
            Type::ARRAY,
            $typedGetterFn,
            $typedConstructorFn
        );
    }

}