<?php

namespace Woof;

use PHPUnit\Framework\TestCase;
use TestHelper;

/**
 * FileDataStorage のテストです。
 *
 * このテストクラスでは物理ファイルの入出力が発生するため、
 * setUp() でテスト用の一時ディレクトリのクリーニングとテストデータのコピーを行っています。
 *
 * @coversDefaultClass Woof\FileDataStorage
 */
class FileDataStorageTest extends TestCase
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
        $datadir = TEST_DATA_DIR . "/FileDataStorage";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);

        $this->tmpdir = $tmpdir;
    }

    /**
     * 指定したパスのファイル内容が取得できることと、存在しない場合は代替値が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     */
    public function testGet(): void
    {
        $tmpdir   = $this->tmpdir;
        $obj      = new FileDataStorage($tmpdir);
        $expected = file_get_contents("{$tmpdir}/test01/sample.txt");
        $this->assertSame($expected, $obj->get("test01/sample.txt"));
        $this->assertSame($expected, $obj->get("test01/sample.txt", "alternative"));
        $this->assertSame("alternative", $obj->get("test01/notfound.txt", "alternative"));
    }

    /**
     * 指定したファイルが存在するかどうかを正しく判定できることを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     */
    public function testContains(): void
    {
        $obj = new FileDataStorage($this->tmpdir);
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
        $obj      = new FileDataStorage($tmpdir);
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
        $obj    = new FileDataStorage($tmpdir);
        $obj->append("test02/test.log", "first line" . PHP_EOL);
        $obj->append("test02/test.log", "second line" . PHP_EOL);
        $obj->append("test02/test.log", "third line" . PHP_EOL);

        $expected = "first line" . PHP_EOL . "second line" . PHP_EOL . "third line" . PHP_EOL;
        $this->assertSame($expected, file_get_contents("{$tmpdir}/test02/test.log"));
    }

    /**
     * 指定された相対パスが正しい絶対パスに変換されることを確認します。
     *
     * @covers ::__construct
     * @covers ::formatPath
     */
    public function testFormatPath(): void
    {
        $tmpdir = $this->tmpdir;
        $obj    = new FileDataStorage($tmpdir);
        $this->assertSame("{$tmpdir}/hoge/index.html", $obj->formatPath("hoge/index.html"));
    }

    /**
     * イニシャル・セグメントに基づいて再帰的にキーを取得できることと、
     * セグメント単位での一致となること (前方一致による誤検知がないこと) を確認します。
     *
     * @param string $prefix イニシャル・セグメント
     * @param array $expected 期待されるキーの配列
     * @covers ::__construct
     * @covers ::getKeys
     * @dataProvider provideTestGetKeys
     */
    public function testGetKeys(string $prefix, array $expected): void
    {
        $tmpdir = $this->tmpdir;
        $obj    = new FileDataStorage($tmpdir);
        $this->assertSame($expected, $obj->getKeys($prefix));
    }

    /**
     * testGetKeys() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetKeys(): array
    {
        $expectedSub = [
            "getkeys01/sub/entry1.txt",
            "getkeys01/sub/entry2.txt",
            "getkeys01/sub/sub2/entry3.txt",
            "getkeys01/sub/sub2/entry4.txt",
        ];
        $expectedAll = [
            ".gitignore",
            "basefile.txt",
            "getkeys01/dummy/entry0.txt",
            "getkeys01/sample.txt",
            "getkeys01/sub/entry1.txt",
            "getkeys01/sub/entry2.txt",
            "getkeys01/sub/sub2/entry3.txt",
            "getkeys01/sub/sub2/entry4.txt",
            "getkeys01/submarine.txt",
            "getkeys01/subway.txt",
            "test01/sample.txt",
        ];

        return [
            ["getkeys01/sub", $expectedSub],
            ["getkeys01/sample.txt", []],
            ["getkeys01/notfound", []],
            ["///getkeys01//sub///", $expectedSub],
            ["", $expectedAll],
        ];
    }
}
