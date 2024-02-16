<?php

namespace Woof\Log;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woof\System\DefaultClock;
use Woof\System\FixedClock;

/**
 * @coversDefaultClass Woof\Log\LoggerBuilder
 */
class LoggerBuilderTest extends TestCase
{
    /**
     * ログレベルの setter と getter およびメソッドチェーンが正しく機能することを確認します。
     *
     * @covers ::getLogLevel
     * @covers ::setLogLevel
     */
    public function testGetLogLevelAndSetLogLevel()
    {
        $obj = new LoggerBuilder();
        $this->assertSame($obj, $obj->setLogLevel(Logger::LEVEL_ALERT));
        $this->assertSame(Logger::LEVEL_ALERT, $obj->getLogLevel());
    }

    /**
     * 不正なログレベルを指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::setLogLevel
     */
    public function testSetLogLevelFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new LoggerBuilder();
        $obj->setLogLevel(-1);
    }

    /**
     * 複数行処理フラグの setter と getter、およびメソッドチェーンが正しく機能することを確認します。
     *
     * @covers ::getMultiple
     * @covers ::setMultiple
     */
    public function testGetMultipleAndSetMultiple()
    {
        $obj = new LoggerBuilder();
        $this->assertSame($obj, $obj->setMultiple(true));
        $this->assertTrue($obj->getMultiple());
    }

    /**
     * LogFormat の setter と getter およびメソッドチェーンが正しく機能することを確認します。
     *
     * @covers ::getFormat
     * @covers ::setFormat
     */
    public function testGetFormatAndSetFormat()
    {
        $format = new DefaultLogFormat("Y/m/d H:i:s");
        $obj    = new LoggerBuilder();
        $this->assertEquals(new DefaultLogFormat(), $obj->getFormat());
        $this->assertSame($obj, $obj->setFormat($format));
        $this->assertSame($format, $obj->getFormat());
    }

    /**
     * LogStorage の setter と getter およびメソッドチェーンが正しく機能することを確認します。
     *
     * @covers ::getStorage
     * @covers ::hasStorage
     * @covers ::setStorage
     */
    public function testGetStorageAndSetStorage()
    {
        $dirname = TEST_DATA_DIR;
        $storage = new FileLogStorage($dirname);
        $obj     = new LoggerBuilder();
        $this->assertFalse($obj->hasStorage());
        $this->assertSame(NullLogStorage::getInstance(), $obj->getStorage());
        $this->assertSame($obj, $obj->setStorage($storage));
        $this->assertTrue($obj->hasStorage());
        $this->assertSame($storage, $obj->getStorage());
    }

    /**
     * Clock の setter と getter およびメソッドチェーンが正しく機能することを確認します。
     *
     * @covers ::getClock
     * @covers ::setClock
     */
    public function testGetClockAndSetClock()
    {
        $clock = new FixedClock(1555555555);
        $obj   = new LoggerBuilder();
        $this->assertSame(DefaultClock::getInstance(), $obj->getClock());
        $this->assertSame($obj, $obj->setClock($clock));
        $this->assertSame($clock, $obj->getClock());
    }

    /**
     * build() メソッドによって Logger インスタンスが正しく生成されることを確認します。
     *
     * @covers ::build
     */
    public function testBuild()
    {
        $storage = new FileLogStorage(TEST_DATA_DIR);
        $obj     = (new LoggerBuilder())->setStorage($storage);
        $this->assertInstanceOf(Logger::class, $obj->build());
    }
}
