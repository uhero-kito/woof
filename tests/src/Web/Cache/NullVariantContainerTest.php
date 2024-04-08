<?php

namespace Woof\Web\Cache;

use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Cache\NullVariantContainer
 */
class NullVariantContainerTest extends TestCase
{
    /**
     * getInstance() が常に同一のインスタンスを返す (シングルトンである) ことを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $obj1 = NullVariantContainer::getInstance();
        $obj2 = NullVariantContainer::getInstance();

        $this->assertInstanceOf(NullVariantContainer::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }

    /**
     * contains() が常に false を返すことを確認します。
     *
     * @covers ::contains
     */
    public function testContainsReturnsFalse(): void
    {
        $obj = NullVariantContainer::getInstance();
        $this->assertFalse($obj->contains("any_id", 3600));
    }

    /**
     * load() が常に LogicException をスローすることを確認します。
     *
     * @covers ::load
     */
    public function testLoadThrowsException(): void
    {
        $id = "test_variant_id_123";
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("This VariantContainer does not support load operation. ID: '{$id}'");

        $obj = NullVariantContainer::getInstance();
        $obj->load($id);
    }

    /**
     * save() が何もせずに常に false を返すことを確認します。
     *
     * @covers ::save
     */
    public function testSaveReturnsFalse(): void
    {
        $obj = NullVariantContainer::getInstance();
        $this->assertFalse($obj->save("any_id", "any_content"));
    }

    /**
     * cleanExpiredVariants() が何もせずに常に 0 を返すことを確認します。
     *
     * @covers ::cleanExpiredVariants
     */
    public function testCleanExpiredVariantsReturnsZero(): void
    {
        $obj = NullVariantContainer::getInstance();
        $this->assertSame(0, $obj->cleanExpiredVariants(3600));
    }
}
