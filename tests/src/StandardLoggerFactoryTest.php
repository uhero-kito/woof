<?php

namespace Woof;

use PHPUnit\Framework\TestCase;
use Woof\Log\DataLogStorage;
use Woof\Log\DefaultLogFormat;
use Woof\Log\Logger;
use Woof\Util\ArrayProperties;

/**
 * @coversDefaultClass Woof\StandardLoggerFactory
 */
class StandardLoggerFactoryTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト用の一時ディレクトリのパスを設定します。
     */
    public function setUp(): void
    {
        $basedir      = TEST_DATA_DIR . "/StandardLoggerFactory";
        $this->tmpdir = "{$basedir}/tmp";
    }

    /**
     * テスト用の Logger インスタンスを生成して返します。
     *
     * @param array $prop Config に設定する配列データ
     * @return Logger 生成されたテスト用 Logger インスタンス
     */
    private function createLoggerByArray(array $prop): Logger
    {
        $obj  = new StandardLoggerFactory();
        $data = new FileDataStorage($this->tmpdir);
        $conf = new Config(new ArrayProperties($prop));
        return $obj->create($conf, $data);
    }

    /**
     * 文字列によるログレベルの指定が、対応する定数に正しく変換されることを確認します。
     *
     * @param string $level 設定等から渡されるログレベル文字列
     * @param int $expected 期待されるログレベル定数
     * @covers ::create
     * @covers ::detectLogLevel
     * @dataProvider provideTestDetectLogLevel
     */
    public function testDetectLogLevel(string $level, int $expected): void
    {
        $conf = [
            "logger" => [
                "loglevel" => $level,
            ],
        ];
        $logger = $this->createLoggerByArray($conf);
        $this->assertSame($expected, $logger->getLogLevel());
    }

    /**
     * testDetectLogLevel() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestDetectLogLevel(): array
    {
        return [
            ["", Logger::LEVEL_ERROR],
            ["notfound", Logger::LEVEL_ERROR],
            ["Error", Logger::LEVEL_ERROR],
            ["alert", Logger::LEVEL_ALERT],
            ["INFO", Logger::LEVEL_INFO],
            ["deBug", Logger::LEVEL_DEBUG],
        ];
    }

    /**
     * DataStorage が未指定の場合に、ログ出力を行わない (NOP) Logger が返されることを確認します。
     *
     * @covers ::create
     */
    public function testCreateWithoutDataStorage(): void
    {
        $obj    = new StandardLoggerFactory();
        $conf   = new Config(new ArrayProperties(["logger" => []]));
        $logger = $obj->create($conf);
        $this->assertSame(Logger::getNopLogger(), $logger);
    }

    /**
     * Config に "logger" セクションが存在しない場合に、ログ出力を行わない (NOP) Logger が返されることを確認します。
     *
     * @covers ::create
     */
    public function testCreateWithoutConfig(): void
    {
        $logger = $this->createLoggerByArray([]);
        $this->assertSame(Logger::getNopLogger(), $logger);
    }

    /**
     * Config に "logger" セクションが存在するが値が空の場合、デフォルト値で Logger が生成されることを確認します。
     *
     * @covers ::create
     */
    public function testCreateByDefault(): void
    {
        $storage = new DataLogStorage(new FileDataStorage($this->tmpdir), "logs/app", ".log");
        $format  = new DefaultLogFormat();
        $logger  = $this->createLoggerByArray(["logger" => []]);
        $this->assertEquals($storage, $logger->getStorage());
        $this->assertEquals($format, $logger->getFormat());
        $this->assertSame(Logger::LEVEL_ERROR, $logger->getLogLevel());
        $this->assertFalse($logger->isMultiple());
    }

    /**
     * Config の "logger" セクションに設定された値が、正しく Logger に反映されることを確認します。
     *
     * @covers ::create
     */
    public function testCreate(): void
    {
        $prop = [
            "logger" => [
                "dirname"  => "test1",
                "prefix"   => "sample",
                "loglevel" => "info",
                "multiple" => "yes",
                "format"   => "Y/m/d H:i:s",
            ],
        ];
        $storage = new DataLogStorage(new FileDataStorage($this->tmpdir), "test1/sample", ".log");
        $format  = new DefaultLogFormat("Y/m/d H:i:s");
        $logger  = $this->createLoggerByArray($prop);
        $this->assertEquals($storage, $logger->getStorage());
        $this->assertEquals($format, $logger->getFormat());
        $this->assertSame(Logger::LEVEL_INFO, $logger->getLogLevel());
        $this->assertTrue($logger->isMultiple());
    }
}
