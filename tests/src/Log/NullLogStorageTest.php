<?php

namespace Woof\Log;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Log\NullLogStorage
 */
class NullLogStorageTest extends TestCase
{
    /**
     * getInstance() を複数回呼び出した時に同一のインスタンスが返されることを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstance()
    {
        $obj1 = NullLogStorage::getInstance();
        $obj2 = NullLogStorage::getInstance();
        $this->assertInstanceOf(NullLogStorage::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }

    /**
     * write() メソッドが常に true を返すことを確認します。
     *
     * @covers ::write
     */
    public function testWrite()
    {
        $obj = NullLogStorage::getInstance();
        $this->assertTrue($obj->write("test", 1555555555, Logger::LEVEL_ERROR));
    }
}
