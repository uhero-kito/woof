<?php

namespace Woof\Log;

use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\System\FileSystemException;

/**
 * FileLogStorage のテストです。
 *
 * このテストクラスではテスト時に物理ファイルの入出力が発生するため、
 * setUp() で一時ディレクトリのクリーニングを行っています。
 * また、異なる日付におけるファイル名生成の挙動を確認するため、一時的にシステムのタイムゾーンを
 * "Asia/Tokyo" に変更しています。
 *
 * @coversDefaultClass Woof\Log\FileLogStorage
 */
class FileLogStorageTest extends TestCase
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
    const DATA_DIR = TEST_DATA_DIR . "/Log/FileLogStorage";

    /**
     * テスト用のディレクトリの準備とタイムゾーンの固定を行います。
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
     * コンストラクタ引数に存在しないディレクトリ名を指定した場合に
     * FileSystemException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByInvalidDirectory(): void
    {
        $this->expectException(FileSystemException::class);
        new FileLogStorage(self::DATA_DIR . "/notfound");
    }

    /**
     * 不正な prefix を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByInvalidPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileLogStorage($this->tmpdir, "ng/bad prefix");
    }

    /**
     * 日付をまたぐ複数のログを書き込み、それぞれ正しい日付のファイル名で出力されることを確認します。
     *
     * @covers ::__construct
     * @covers ::write
     * @covers ::<private>
     */
    public function testWrite(): void
    {
        $obj = new FileLogStorage($this->tmpdir);
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
     * 第 2 引数を指定することでログファイル名をカスタマイズできることを確認します。
     *
     * @covers ::__construct
     * @covers ::write
     * @covers ::<private>
     */
    public function testWriteByPrefix(): void
    {
        $obj = new FileLogStorage($this->tmpdir, "test");
        $obj->write("This is test", 1555500000, Logger::LEVEL_ERROR);
        $log = "{$this->tmpdir}/test-20190417.log";
        $this->assertFileExists($log);
        $this->assertSame("This is test" . PHP_EOL, file_get_contents($log));
    }
}
