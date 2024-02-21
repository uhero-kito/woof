<?php

namespace Woof;

use PHPUnit\Framework\TestCase;
use Woof\Util\ArrayProperties;
use Woof\Util\FileProperties;

/**
 * @coversDefaultClass Woof\Config
 */
class ConfigTest extends TestCase
{
    /**
     * テスト用の Config インスタンスを生成して返します。
     *
     * @return Config テスト用のインスタンス
     */
    public function createTestObject(): Config
    {
        $datadir = TEST_DATA_DIR . "/Config/subjects";
        return new Config(new FileProperties($datadir));
    }

    /**
     * 指定したキーの設定値が正しく整数 (int) として取得できることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param int $expected 期待される整数値
     * @covers ::__construct
     * @covers ::getInt
     * @dataProvider provideTestGetInt
     */
    public function testGetInt(string $name, int $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getInt("test01.{$name}", 42));
    }

    /**
     * testGetInt() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetInt(): array
    {
        return [
            ["num.a", 4],
            ["num.b", -3],
            ["num.c", 10],
            ["num.d", 2],
            ["num.e", 42],
            ["num.f", 42],
            ["num.g", 1],
            ["num.h", 0],
            ["num.i", 0],
        ];
    }

    /**
     * 指定したキーの設定値が正しく浮動小数点数 (float) として取得できることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param float $expected 期待される浮動小数点数値
     * @covers ::__construct
     * @covers ::getFloat
     * @dataProvider provideTestGetFloat
     */
    public function testGetFloat(string $name, float $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getFloat("test01.{$name}", 34.5));
    }

    /**
     * testGetFloat() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetFloat(): array
    {
        return [
            ["num.a", 4.75],
            ["num.b", -3.125],
            ["num.c", 10.0],
            ["num.d", 2.25],
            ["num.e", 34.5],
            ["num.f", 34.5],
            ["num.g", 1.0],
            ["num.h", 0.0],
            ["num.i", 0.0],
        ];
    }

    /**
     * 指定したキーの設定値を整数 (int) として取得する際、最小値・最大値の制限が正しく適用されることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param int $expected 制限が適用された期待値
     * @covers ::__construct
     * @covers ::getInt
     * @covers ::<private>
     * @dataProvider provideTestGetIntByMinMax
     */
    public function testGetIntByMinMax(string $name, int $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getInt("test01.{$name}", 10, -32, 16));
    }

    /**
     * testGetIntByMinMax() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetIntByMinMax(): array
    {
        return [
            ["minmax.a", -32],
            ["minmax.b", -32],
            ["minmax.c", -31],
            ["minmax.d", 15],
            ["minmax.e", 16],
            ["minmax.f", 16],
            ["minmax.z", 10],
        ];
    }

    /**
     * 指定したキーの設定値を浮動小数点数 (float) として取得する際、最小値・最大値の制限が正しく適用されることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param float $expected 制限が適用された期待値
     * @covers ::__construct
     * @covers ::getFloat
     * @covers ::<private>
     * @dataProvider provideTestGetFloatByMinMax
     */
    public function testGetFloatByMinMax(string $name, float $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getFloat("test01.{$name}", 10.0, -32.0, 16.0));
    }

    /**
     * testGetFloatByMinMax() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetFloatByMinMax(): array
    {
        return [
            ["minmax.g", -32.0],
            ["minmax.h", -32.0],
            ["minmax.i", -31.75],
            ["minmax.j", 15.875],
            ["minmax.k", 16.0],
            ["minmax.l", 16.0],
            ["minmax.z", 10.0],
        ];
    }

    /**
     * 指定したキーの設定値が正しく文字列 (string) として取得できることと、変換可能な値が文字列表現になることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param string $expected 期待される文字列
     * @covers ::__construct
     * @covers ::getString
     * @covers ::<private>
     * @dataProvider provideTestGetString
     */
    public function testGetString(string $name, string $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getString("test01.{$name}", "default"));
    }

    /**
     * testGetString() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetString(): array
    {
        return [
            ["key1.foo","123"],
            ["key1.bar", "asdf"],
            ["key1.buzz", "true"],
            ["key1.notfound", "default"],
            ["key1", "default"],
            ["key2", "default"],
            ["key3", "default"],
            ["key4", "false"],
            ["key5", "null"],
        ];
    }

    /**
     * 指定したキーの設定値が正しく配列 (array) として取得できることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param array $expected 期待される配列
     * @covers ::__construct
     * @covers ::getArray
     * @dataProvider provideTestGetArray
     */
    public function testGetArray(string $name, array $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getArray("test01.{$name}", ["default"]));
    }

    /**
     * 指定したキー以下の設定値が、新しい Config オブジェクトとして正しく切り出せることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param array $expected 切り出された設定として期待される配列 (内部比較用)
     * @covers ::__construct
     * @covers ::getSubConfig
     * @dataProvider provideTestGetArray
     */
    public function testGetSubConfig($name, array $expected): void
    {
        $obj = $this->createTestObject();
        $c   = new Config(new ArrayProperties($expected));
        $this->assertEquals($c, $obj->getSubConfig("test01.{$name}", ["default"]));
    }

    /**
     * testGetArray() および testGetSubConfig() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetArray(): array
    {
        return [
            ["key1", ["foo" => 123, "bar" => "asdf", "buzz" => true]],
            ["key1.foo", ["default"]],
            ["key2", []],
            ["key3", []],
            ["key4", ["default"]],
            ["key5", ["default"]],
            ["key9", ["default"]],
        ];
    }

    /**
     * 存在しないキーを指定した場合に、それぞれの取得メソッドが正しく代替値 (デフォルト値) を返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::getInt
     * @covers ::getString
     * @covers ::getArray
     * @covers ::getBool
     */
    public function testGetByDefault(): void
    {
        $c   = new Config(new ArrayProperties([]));
        $obj = $this->createTestObject();
        $this->assertSame(0, $obj->getInt("notfound.key1"));
        $this->assertSame(0.0, $obj->getFloat("notfound.key1"));
        $this->assertSame("", $obj->getString("notfound.key1"));
        $this->assertSame([], $obj->getArray("notfound.key1"));
        $this->assertEquals($c, $obj->getSubConfig("notfound.key1"));
        $this->assertSame(false, $obj->getBool("notfound.key1"));
    }

    /**
     * 指定したキーの設定値が、様々な文字列表現を含めて正しく論理値 (bool) として取得できることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param bool $expected 期待される論理値
     * @covers ::__construct
     * @covers ::getBool
     * @covers ::<private>
     * @dataProvider provideTestGetBool
     */
    public function testGetBool(string $name, bool $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->getBool("test01.{$name}", false));
    }

    /**
     * testGetBool() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetBool(): array
    {
        return [
            ["key1.foo", false],
            ["key1.bar", false],
            ["key1.buzz", true],
            ["key6.a", true],
            ["key6.b", false],
            ["key6.c", true],
            ["key6.d", false],
            ["key6.e", true],
            ["key6.f", false]
        ];
    }

    /**
     * 指定したキーが存在するかどうかを正しく判定できることを確認します。
     *
     * @param string $name テストする設定キー名
     * @param bool $expected 存在するかどうかの期待値
     * @covers ::__construct
     * @covers ::contains
     * @dataProvider provideTestContains
     */
    public function testContains(string $name, bool $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->contains("test01.{$name}"));
    }

    /**
     * testContains() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestContains(): array
    {
        return [
            ["key1.foo", true],
            ["key1.notfound", false],
            ["key2", true],
            ["key3", true],
            ["key4", true],
            ["key5", true],
            ["key9", false],
        ];
    }
}
