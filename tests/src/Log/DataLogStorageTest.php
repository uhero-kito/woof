<?php

namespace Woof\Log;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\FileDataStorage;

/**
 * DataLogStorage のテストです。
 *
 * このテストクラスでは DataStorage の具象クラス (FileDataStorage)
 * を通じて物理ファイルの入出力が発生するため、
 * setUp() で一時ディレクトリのクリーニングを行っています。
 *
 * また、異なる日付におけるファイル名生成の挙動を確認するため、一時的にシステムのタイムゾーンを
 * "Asia/Tokyo" に変更しています。
 *
 * @coversDefaultClass Woof\Log\DataLogStorage
 */
class DataLogStorageTest extends TestCase
{
    /**
     * テスト用の一時ファイルを出力するディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト実行前の元のタイムゾーン設定を保持します。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * テストデータが配置されるベースディレクトリです。
     *
     * @var string
     */
    const DATA_DIR = TEST_DATA_DIR . "/Log/DataLogStorage";

    /**
     * テスト用の一時ディレクトリの準備とタイムゾーンの固定を行います。
     */
    public function setUp(): void
    {
        $tmpdir = self::DATA_DIR . "/tmp";
        TestHelper::cleanDirectory($tmpdir);

        $this->tmpdir          = $tmpdir;
        $this->defaultTimezone = ini_set("timezone", "Asia/Tokyo");
    }

    /**
     * 固定したタイムゾーンを元の状態に戻します。
     */
    public function tearDown(): void
    {
        ini_set("timezone", $this->defaultTimezone);
    }

    /**
     * コンストラクタの prefix に空文字列を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByInvalidPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataLogStorage(new FileDataStorage($this->tmpdir), "");
    }

    /**
     * 日付をまたぐ複数のログを書き込み、それぞれ正しい日付のファイル名 (キー) で出力されることを確認します。
     *
     * @covers ::__construct
     * @covers ::write
     * @covers ::<private>
     */
    public function testWrite(): void
    {
        $obj = new DataLogStorage(new FileDataStorage($this->tmpdir));
        foreach (["this", "is", "test"] as $content) {
            $obj->write($content, 1555500000, Logger::LEVEL_DEBUG);
        }
        foreach (["Hello", "World"] as $content) {
            $obj->write($content, 1555555555, Logger::LEVEL_DEBUG);
        }

        $expected1 = implode(PHP_EOL, ["this", "is", "test"]) . PHP_EOL;
        $expected2 = implode(PHP_EOL, ["Hello", "World"]) . PHP_EOL;
        $logPath1  = "{$this->tmpdir}/app-20190417.log";
        $logPath2  = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath1);
        $this->assertSame($expected1, file_get_contents($logPath1));
        $this->assertFileExists($logPath2);
        $this->assertSame($expected2, file_get_contents($logPath2));
    }

    /**
     * 第 2 引数以降を指定することでログのファイル名 (キー) をカスタマイズできることを確認します。
     *
     * @covers ::__construct
     * @covers ::write
     * @covers ::<private>
     */
    public function testWriteBySuffix(): void
    {
        $obj = new DataLogStorage(new FileDataStorage($this->tmpdir), "logs/debug", ".dat");
        $obj->write("This is test", 1555500000, Logger::LEVEL_ERROR);
        $log = "{$this->tmpdir}/logs/debug-20190417.dat";
        $this->assertFileExists($log);
        $this->assertSame("This is test" . PHP_EOL, file_get_contents($log));
    }
}
