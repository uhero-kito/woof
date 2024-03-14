<?php

namespace Woof\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Woof\Util\IniDecoder;
use Woof\Util\JsonDecoder;

/**
 * @coversDefaultClass Woof\Util\FileProperties
 */
class FilePropertiesTest extends TestCase
{
    /**
     * テストデータが配置されているディレクトリのパスです。
     *
     * @var string
     */
    const TEST_DIR = TEST_DATA_DIR . "/Util/FileProperties/subjects";

    /**
     * デフォルトの StringDecoder リストが正しく取得できることを確認します。
     *
     * @covers ::getDefaultStringDecoderList
     */
    public function testGetDefaultStringDecoderList(): void
    {
        $expected = [
            "ini"  => IniDecoder::getInstance(),
            "json" => JsonDecoder::getInstance(),
        ];
        $this->assertSame($expected, FileProperties::getDefaultStringDecoderList());
    }

    /**
     * コンストラクタに指定したデコーダリスト内に無効なキーや値が含まれていた場合、
     * それらが除外され、有効なデコーダのみが設定されることを確認します。
     *
     * @covers ::__construct
     * @covers ::<private>
     */
    public function testConstructFiltersInvalidDecoders(): void
    {
        $je = JsonDecoder::getInstance();
        $ie = IniDecoder::getInstance();

        $validList   = ["txt" => $je, "cfg" => $ie];
        $invalidList = [
            "x/y/z" => $je,
            "txt"   => $je,
            "var"   => new stdClass(),
            "cfg"   => $ie,
        ];

        $tmpdir = self::TEST_DIR;
        $obj1   = new FileProperties($tmpdir, $validList);
        $obj2   = new FileProperties($tmpdir, $invalidList);
        $this->assertEquals($obj1, $obj2);
    }

    /**
     * 不正なキー名を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param string $key 不正なキー名
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGetFailByInvlidKey
     */
    public function testGetFailByInvalidKey(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new FileProperties(self::TEST_DIR);
        $obj->get($key);
    }

    /**
     * testGetFailByInvalidKey() のための不正なキー名のテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetFailByInvlidKey(): array
    {
        return [
            [""],
            ["test 01.invaild/key"],
        ];
    }

    /**
     * 単一階層の INI ファイルから正しい設定値が取得できることを確認します。
     *
     * @param string $key INI ファイル内のキー名
     * @param mixed $expected 期待される設定値
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGetByIni
     */
    public function testGetByIni(string $key, $expected): void
    {
        $obj = new FileProperties(self::TEST_DIR);
        $this->assertSame($expected, $obj->get("test01.{$key}"));
    }

    /**
     * testGetByIni() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetByIni(): array
    {
        return [
            ["aaa", 123],
            ["bbb", true],
            ["ccc", false],
            ["ddd", null],
            ["eee", "hogehoge"],
            ["fff", "-45.75"],
            ["ggg", null],
        ];
    }

    /**
     * 存在しないキーを指定した場合に代替値 (デフォルト値) が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     */
    public function testGetWithDefaultValue(): void
    {
        $obj = new FileProperties(self::TEST_DIR);
        $this->assertSame(123, $obj->get("test01.aaa", 234));
        $this->assertSame(234, $obj->get("test01.ggg", 234));
    }

    /**
     * セクションを持つ INI ファイルから、ドット区切りで正しい設定値が取得できることを確認します。
     *
     * @param string $key 階層を含むキー名
     * @param mixed $expected 期待される設定値
     * @dataProvider provideTestGetBySectionedIni
     */
    public function testGetBySectionedIni(string $key, $expected): void
    {
        $obj = new FileProperties(self::TEST_DIR);
        $this->assertSame($expected, $obj->get("test02.{$key}"));
    }

    /**
     * testGetBySectionedIni() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetBySectionedIni(): array
    {
        return [
            ["category1", ["aaa" => "hoge", "bbb" => "fuga", "ccc" => "piyo"]],
            ["category2.aaa", "hogehoge"],
            ["category3", null],
            ["category4.aaa", null],
        ];
    }

    /**
     * JSON ファイルから正しい設定値が取得できることを確認します。
     *
     * @param string $key 階層を含むキー名
     * @param mixed $expected 期待される設定値
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGetByJson
     */
    public function testGetByJson(string $key, $expected): void
    {
        $obj = new FileProperties(self::TEST_DIR);
        $this->assertSame($expected, $obj->get("test04.{$key}"));
    }

    /**
     * testGetByJson() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetByJson(): array
    {
        return [
            ["key1", ["aaa" => "hoge", "bbb" => "fuga"]],
            ["key2.ccc", "piyo"],
        ];
    }

    /**
     * ファイル名 (第一階層) を単体で指定した場合に、ファイル内の全ての設定値が配列として取得できることを確認します。
     *
     * @param string $key 取得するファイル名 (第一階層のキー)
     * @param mixed $expected 期待される設定値の配列
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGetByWholeFile
     */
    public function testGetByWholeFile(string $key, $expected): void
    {
        $tmpdir = self::TEST_DIR;
        $obj    = new FileProperties($tmpdir);
        $this->assertSame($expected, $obj->get($key));
    }

    /**
     * testGetByWholeFile() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetByWholeFile(): array
    {
        $tmpdir = self::TEST_DIR;
        $test01 = parse_ini_file("{$tmpdir}/test01.ini", true, INI_SCANNER_TYPED);
        $test02 = parse_ini_file("{$tmpdir}/test02.ini", true, INI_SCANNER_TYPED);
        $test04 = json_decode(file_get_contents("{$tmpdir}/test04.json"), true);
        return [
            ["test01", $test01],
            ["test02", $test02],
            ["test03", []],
            ["test04", $test04],
            ["test09", null],
        ];
    }

    /**
     * 指定されたキー名がファイル内に存在するかどうかを正しく判定できることを確認します。
     *
     * @param string $key 確認するキー名
     * @param bool $expected 期待される判定結果
     * @covers ::__construct
     * @covers ::contains
     * @covers ::<private>
     * @dataProvider provideTestContains
     */
    public function testContains(string $key, bool $expected): void
    {
        $obj = new FileProperties(self::TEST_DIR);
        $this->assertSame($expected, $obj->contains($key));
    }

    /**
     * testContains() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestContains(): array
    {
        return [
            ["test01", true],
            ["test09", false],
            ["test01.aaa", true],
            ["test01.ggg", false],
            ["test02.category1", true],
            ["test02.category2.ccc", true],
            ["test02.category2.eee", false],
            ["test05.key1", true],
            ["test05.key2", true],
            ["test05.key3", true],
        ];
    }
}
