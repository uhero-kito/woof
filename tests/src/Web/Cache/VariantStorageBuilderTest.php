<?php

namespace Woof\Web\Cache;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woof\System\ArrayRandom;
use Woof\System\DefaultClock;
use Woof\System\DefaultRandom;
use Woof\System\FixedClock;

/**
 * @coversDefaultClass Woof\Web\Cache\VariantStorageBuilder
 */
class VariantStorageBuilderTest extends TestCase
{
    /**
     * VariantContainer の設定と取得が正しく行えることを確認します。
     *
     * @covers ::setVariantContainer
     * @covers ::getVariantContainer
     * @covers ::hasVariantContainer
     */
    public function testVariantContainerAccessors(): void
    {
        $obj = new VariantStorageBuilder();
        $this->assertFalse($obj->hasVariantContainer());

        $container = new FileVariantContainer(TEST_DATA_DIR);
        $this->assertSame($obj, $obj->setVariantContainer($container));
        $this->assertTrue($obj->hasVariantContainer());
        $this->assertSame($container, $obj->getVariantContainer());
    }

    /**
     * MaxAge の設定と取得が正しく機能することを確認します。
     *
     * @covers ::setMaxAge
     * @covers ::getMaxAge
     */
    public function testMaxAgeAccessors(): void
    {
        $obj = new VariantStorageBuilder();
        $this->assertSame(3600, $obj->getMaxAge());
        $this->assertSame($obj, $obj->setMaxAge(7200));
        $this->assertSame(7200, $obj->getMaxAge());
    }

    /**
     * MaxAge に 0 以下の無効な値を設定しようとした場合、例外がスローされることを確認します。
     *
     * @param int $invalidMaxAge
     *
     * @covers ::setMaxAge
     * @dataProvider provideInvalidMaxAgeValues
     */
    public function testSetMaxAgeThrowsExceptionForInvalidValues(int $invalidMaxAge): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid max-age value: {$invalidMaxAge}");

        $builder = new VariantStorageBuilder();
        $builder->setMaxAge($invalidMaxAge);
    }

    /**
     * @return array
     */
    public function provideInvalidMaxAgeValues(): array
    {
        return [
            "zero"          => [0],
            "negative-one"  => [-1],
            "negative-1800" => [-1800],
        ];
    }

    /**
     * GcProbability の設定と取得、およびデフォルト値が正しく機能することを確認します。
     *
     * @covers ::setGcProbability
     * @covers ::getGcProbability
     */
    public function testGcProbabilityAccessors(): void
    {
        // 未設定時のデフォルト値が 0.0 であることを確認します
        $obj = new VariantStorageBuilder();
        $this->assertSame(0.0, $obj->getGcProbability());

        // 任意の値を設定して取得できることを確認します
        $this->assertSame($obj, $obj->setGcProbability(0.5));
        $this->assertSame(0.5, $obj->getGcProbability());

        // 境界値 (0.0 と 1.0) の設定が可能であることを確認します
        $obj->setGcProbability(0.0);
        $this->assertSame(0.0, $obj->getGcProbability());
        $obj->setGcProbability(1.0);
        $this->assertSame(1.0, $obj->getGcProbability());
    }

    /**
     * GcProbability に 0.0 未満または 1.0 より大きい無効な値を設定しようとした場合、
     * 例外がスローされることを確認します。
     *
     * @param float $invalidProbability
     *
     * @covers ::setGcProbability
     * @dataProvider provideInvalidGcProbabilityValues
     */
    public function testSetGcProbabilityThrowsExceptionForInvalidValues(float $invalidProbability): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid GC probability value: {$invalidProbability}");

        $builder = new VariantStorageBuilder();
        $builder->setGcProbability($invalidProbability);
    }

    /**
     * @return array
     */
    public function provideInvalidGcProbabilityValues(): array
    {
        return [
            "less-than-zero"   => [-0.1],
            "negative-one"     => [-1.0],
            "greater-than-one" => [1.1],
            "two"              => [2.0],
        ];
    }

    /**
     * Clock の設定と取得、およびデフォルト値が正しく機能することを確認します。
     *
     * @covers ::setClock
     * @covers ::getClock
     */
    public function testClockAccessors(): void
    {
        $obj = new VariantStorageBuilder();
        $this->assertSame(DefaultClock::getInstance(), $obj->getClock());

        $clock = new FixedClock(1555555555);
        $this->assertSame($obj, $obj->setClock($clock));
        $this->assertSame($clock, $obj->getClock());
    }

    /**
     * Random の設定と取得、およびデフォルト値が正しく機能することを確認します。
     *
     * @covers ::setRandom
     * @covers ::getRandom
     */
    public function testRandomAccessors(): void
    {
        $obj = new VariantStorageBuilder();
        $this->assertSame(DefaultRandom::getInstance(), $obj->getRandom());

        $random = new ArrayRandom([123, 456]);
        $this->assertSame($obj, $obj->setRandom($random));
        $this->assertSame($random, $obj->getRandom());
    }
}
