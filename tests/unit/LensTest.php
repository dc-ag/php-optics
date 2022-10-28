<?php
declare(strict_types=1);

namespace dcAg\phpOptics\tests\unit;


use dcAG\phpOptics\Lens;
use dcAg\phpOptics\tests\fixtures\ExampleInnerData;
use dcAg\phpOptics\tests\fixtures\ExampleOuterData;
use dcAG\phpOptics\Type;
use PHPUnit\Framework\TestCase;

class LensTest extends TestCase
{

    protected ?Lens $outerLens = null;
    protected ?Lens $innerLens = null;
    protected ?Lens $compositLens = null;
    protected ExampleOuterData|null $outerDataToTest = null;
    protected ExampleInnerData|null $innerDataToTest = null;
    protected string $innerDataValueToReplace = "string1";
    protected string $innerDataValueToReplaceWith = "string2";
    protected ExampleInnerData|null $expectedInnerReplacement = null;
    protected ExampleOuterData|null $expectedOuterReplacement = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerDataToTest = new ExampleInnerData(1, true, $this->innerDataValueToReplace);
        $this->outerDataToTest = new ExampleOuterData($this->innerDataToTest, "someOuterString1");
        $this->expectedInnerReplacement = new ExampleInnerData(1, true, $this->innerDataValueToReplaceWith);
        $this->expectedOuterReplacement = new ExampleOuterData($this->expectedInnerReplacement, "someOuterString1");

    }

    public function testConstruct()
    {

        $this->createAndSetLenses();
        $this->assertTrue(true);
    }

    public function testUpdate()
    {
        $this->createAndSetLenses();
        $actualInner = $this->innerLens->update($this->innerDataToTest, $this->innerDataValueToReplaceWith);
        $this->assertTrue($this->expectedInnerReplacement->equals($actualInner));
        $actualOuter = $this->outerLens->update($this->outerDataToTest, $this->expectedInnerReplacement);
        $this->assertTrue($this->expectedOuterReplacement->equals($actualOuter));
    }

    public function testGet()
    {
        $this->createAndSetLenses();
        $actualInner = $this->innerLens->get($this->innerDataToTest);
        $this->assertEquals($this->innerDataValueToReplace, $actualInner);
        $actualOuter = $this->outerLens->get($this->outerDataToTest);
        $this->assertTrue($this->innerDataToTest->equals($actualOuter));
    }

    public function testGetAfterSet()
    {
        $this->createAndSetLenses();
        $newInner = $this->innerLens->update($this->innerDataToTest, $this->innerDataValueToReplaceWith);
        $newOuter = $this->outerLens->update($this->outerDataToTest, $newInner);
        $queriedNewInner = $this->outerLens->get($newOuter);
        $queriedNewInnerValue = $this->innerLens->get($queriedNewInner);
        $this->assertEquals($this->innerDataValueToReplaceWith, $queriedNewInnerValue);
    }

    public function testCompose()
    {
        $this->createAndSetLenses();
        $this->compositLens = $this->outerLens->compose($this->innerLens);
        $this->assertTrue(true);
    }

    public function testComposedGet()
    {
        $this->createAndSetLenses();
        $this->compositLens = $this->outerLens->compose($this->innerLens);
        $actual = $this->compositLens->get($this->outerDataToTest);
        $this->assertEquals($this->innerDataValueToReplace, $actual);
    }

    public function testComposedSet()
    {
        $this->createAndSetLenses();
        $this->compositLens = $this->outerLens->compose($this->innerLens);
        $actual = $this->compositLens->update($this->outerDataToTest, $this->innerDataValueToReplaceWith);
        $this->assertTrue($this->expectedOuterReplacement->equals($actual));
    }

    /**
     * @return void
     */
    protected function createAndSetLenses(): void
    {
        if (null === $this->innerLens) {
            $this->innerLens = new Lens(
                ExampleInnerData::class,
                Type::STRING,
                fn(ExampleInnerData $data): string => $data->someString,
                fn(ExampleInnerData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                    $data->someInt,
                    $data->someBool,
                    $replacement
                )
            );
        }
        if (null === $this->outerLens) {
            $this->outerLens = new Lens(
                ExampleOuterData::class,
                ExampleInnerData::class,
                fn(ExampleOuterData $data): ExampleInnerData => $data->innerData,
                fn(ExampleOuterData $data, ExampleInnerData $replacement): ExampleOuterData => new ExampleOuterData(
                    $replacement,
                    $data->someString
                )
            );
        }
    }

    public function testCannotComposeWrongTypes()
    {
        $this->createAndSetLenses();
        $this->expectException(\InvalidArgumentException::class);
        $this->compositLens = $this->innerLens->compose($this->outerLens);
    }

    public function testCannotCreateWithWrongGetterParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleOuterData $data): string => $data->someString,
            fn(ExampleInnerData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithWrongGetterReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): int => $data->someString,
            fn(ExampleInnerData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithMissingGetterParameterType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn($data): string => $data->someString,
            fn(ExampleInnerData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithMissingGetterReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data) => $data->someString,
            fn(ExampleInnerData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithWrongConstructorParamterTypeOne()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn(ExampleOuterData $data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithWrongConstructorParamterTypeTwo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn(ExampleInnerData $data, int $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithWrongConstructorReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn(ExampleInnerData $data, string $replacement): ExampleOuterData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithMissingConstructorParameterTypeOne()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn($data, string $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithMissingConstructorParameterTypeTwo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn(ExampleInnerdata $data, $replacement): ExampleInnerData => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testCannotCreateWithMissingConstructorReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens= new Lens(
            ExampleInnerData::class,
            Type::STRING,
            fn(ExampleInnerData $data): string => $data->someString,
            fn(ExampleInnerdata $data, string $replacement) => new ExampleInnerData(
                $data->someInt,
                $data->someBool,
                $replacement
            )
        );
    }

    public function testGetFromZipped()
    {
        $this->createAndSetLenses();
        $innerLens2 = new Lens(
            ExampleInnerData::class,
            Type::INT,
            fn(ExampleInnerData $data): int => $data->someInt,
            fn(ExampleInnerData $data, int $replacement): ExampleInnerData => new ExampleInnerData(
                $replacement,
                $data->someBool,
                $data->someString
            )
        );

        $zippedLens = $this->innerLens->zipWith($innerLens2);

        $expected = [$this->innerDataValueToReplace, 1];
        $actual = $zippedLens->get($this->innerDataToTest);

        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function testSetViaZipped()
    {
        $this->createAndSetLenses();
        $innerLens2 = new Lens(
            ExampleInnerData::class,
            Type::INT,
            fn(ExampleInnerData $data): int => $data->someInt,
            fn(ExampleInnerData $data, int $replacement): ExampleInnerData => new ExampleInnerData(
                $replacement,
                $data->someBool,
                $data->someString
            )
        );

        $zippedLens = $this->innerLens->zipWith($innerLens2);

        $newInt = 42;
        $newString = 'new string';

        $expected = new ExampleInnerData($newInt, $this->innerDataToTest->someBool, $newString);

        $actual = $zippedLens->update($this->innerDataToTest, [$newString, $newInt]);

        $this->assertTrue($expected->equals($actual));
    }

    public function testCannotZipForWrongTypes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createAndSetLenses();
        $zippedLens = $this->innerLens->zipWith($this->outerLens);
    }

    public function testCanCreateFromProperty()
    {
        $newLens = Lens::fromProperty(ExampleInnerData::class, 'someString');
        $this->assertTrue(true);
    }

    public function testGetWithLensFromProperty()
    {
        $newLens = Lens::fromProperty(ExampleInnerData::class, 'someString');
        $expected = $this->innerDataValueToReplace;
        $actual = $newLens->get($this->innerDataToTest);
        $this->assertEquals($expected, $actual);
    }

    public function testSetWithLensFromProperty()
    {
        $newLens = Lens::fromProperty(ExampleInnerData::class, 'someString');
        $expected = $this->expectedInnerReplacement;
        $actual = $newLens->update($this->innerDataToTest, $this->innerDataValueToReplaceWith);
        $this->assertTrue($expected->equals($actual));
    }

    public function testCannotCreateLensWithNonExistentProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $newLens = Lens::fromProperty(ExampleInnerData::class, 'foo');
    }

}
