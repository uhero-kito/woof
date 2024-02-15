<?php

namespace Woof\System;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TestHelper;

/**
 * FileHandler のテストです。
 *
 * このテストクラスでテスト時に物理ファイルの入出力を行うため
 * setUp() 内でテスト用の一時ディレクトリのクリーニングとテストデータのコピーを行います。
 *
 * @coversDefaultClass Woof\System\FileHandler
 */
class FileHandlerTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト用の一時ディレクトリの準備とテストデータのコピーを行います。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/System/FileHandler";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);

        $this->tmpdir = $tmpdir;
    }

    /**
     * コンストラクタ引数に空文字列を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileHandler("");
    }

    /**
     * コンストラクタ引数に存在しないディレクトリ名を指定した場合に FileSystemException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByNonExistingName(): void
    {
        $this->expectException(FileSystemException::class);
        new FileHandler(TEST_DATA_DIR . "/notfound");
    }

    /**
     * 指定された相対パスが正しい絶対パスに変換されることを確認します。
     *
     * @covers ::__construct
     * @covers ::formatFullpath
     */
    public function testFormatFullPath(): void
    {
        $tmpdir = $this->tmpdir;
        $obj    = new FileHandler($tmpdir);
        $this->assertSame("{$tmpdir}/hoge/index.html", $obj->formatFullpath("hoge/index.html"));
    }

    /**
     * パス内の不要なスラッシュやドット (".", "..") が正しく解決されることを確認します。
     *
     * @param string $path 入力となる相対パス
     * @param string $expected 期待される解決後の相対パス
     * @covers ::__construct
     * @covers ::formatFullpath
     * @covers ::cleanPath
     * @dataProvider provideTestCleanPath
     */
    public function testCleanPath(string $path, string $expected): void
    {
        $tmpdir = $this->tmpdir;
        $obj    = new FileHandler($tmpdir);
        $this->assertSame("{$tmpdir}/{$expected}", $obj->formatFullpath($path));
    }

    /**
     * testCleanPath() のためのテストデータ (入力パスと期待される結果のペア) を提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestCleanPath(): array
    {
        return [
            ["//foo/bar///buz//", "foo/bar/buz"],
            ["/./foo/bar/./buz.html", "foo/bar/buz.html"],
            ["../foo/bar/../buz", "foo/buz"],
        ];
    }

    /**
     * 不正なパスを指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param string $path 不正な相対パス
     * @covers ::__construct
     * @covers ::formatFullpath
     * @dataProvider provideTestFormatFullPathFail
     */
    public function testFormatFullPathFail(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new FileHandler($this->tmpdir);
        $obj->formatFullPath($path);
    }

    /**
     * testFormatFullPathFail() のための不正なパスのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormatFullPathFail(): array
    {
        return [
            [""],
            ["/./..///.././"],
        ];
    }

    /**
     * 指定したパスのファイルの内容が正しく取得できることと、存在しない場合は空文字列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     */
    public function testGet(): void
    {
        $tmpdir   = $this->tmpdir;
        $obj      = new FileHandler($tmpdir);
        $expected = file_get_contents("{$tmpdir}/test01/sample.txt");
        $this->assertSame($expected, $obj->get("test01/sample.txt"));
        $this->assertSame("", $obj->get("test02/notfound.txt"));
    }

    /**
     * 指定したファイルが存在するかどうかを正しく判定できることを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     */
    public function testContains(): void
    {
        $obj = new FileHandler($this->tmpdir);
        $this->assertFalse($obj->contains("test01/aaaa.txt"));
        $this->assertTrue($obj->contains("test01/sample.txt"));
    }

    /**
     * 指定したパスにファイルが新規作成され、内容が書き込まれることを確認します。
     *
     * @covers ::__construct
     * @covers ::put
     * @covers ::<private>
     */
    public function testPut(): void
    {
        $tmpdir   = $this->tmpdir;
        $testfile = "{$tmpdir}/test02/newfile.txt";
        $obj      = new FileHandler($tmpdir);
        $this->assertFileDoesNotExist($testfile);
        $obj->put("test02/newfile.txt", "This is test");
        $this->assertFileExists($testfile);
        $this->assertSame("This is test", file_get_contents($testfile));
    }

    /**
     * 既存のファイルに内容が正しく追記されることを確認します。
     *
     * @covers ::__construct
     * @covers ::append
     */
    public function testAppend(): void
    {
        $tmpdir = $this->tmpdir;
        $obj    = new FileHandler($tmpdir);
        $obj->append("test02/test.log", "first line" . PHP_EOL);
        $obj->append("test02/test.log", "second line" . PHP_EOL);
        $obj->append("test02/test.log", "third line" . PHP_EOL);

        $expected = "first line" . PHP_EOL . "second line" . PHP_EOL . "third line" . PHP_EOL;
        $this->assertSame($expected, file_get_contents("{$tmpdir}/test02/test.log"));
    }
}
