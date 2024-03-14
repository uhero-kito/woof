<?php

namespace Woof;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\NullResources
 */
class NullResourcesTest extends TestCase
{
    /**
     * 同一のインスタンスが返されることを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstance(): void
    {
        $obj1 = NullResources::getInstance();
        $obj2 = NullResources::getInstance();
        $this->assertInstanceOf(NullResources::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }

    /**
     * リソースの存在確認に対して、常に false が返されることを確認します。
     *
     * @covers ::contains
     */
    public function testContains(): void
    {
        $obj = NullResources::getInstance();
        $this->assertFalse($obj->contains("key"));
    }

    /**
     * リソースの取得処理において、常に ResourceNotFoundException がスローされることを確認します。
     *
     * @covers ::get
     */
    public function testGet(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        NullResources::getInstance()->get("key");
    }
}
