<?php

namespace Woof\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Util\ArrayProperties
 */
class ArrayPropertiesTest extends TestCase
{
    /**
     * テスト用の ArrayProperties インスタンスを生成します。
     *
     * @return ArrayProperties テスト用インスタンス
     */
    private function createTestObject(): ArrayProperties
    {
        $json = TEST_DATA_DIR . "/Util/ArrayProperties/subjects/data.json";
        $arr  = json_decode(file_get_contents($json), true);
        return new ArrayProperties($arr);
    }

    /**
     * 初期化に用いた配列全体が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $arr = [
            "key1" => [1, 2, 3],
            "key2" => "foo",
            "key3" => "bar",
        ];
        $obj = new ArrayProperties($arr);
        $this->assertSame($arr, $obj->getData());
    }

    /**
     * 不正なキー名を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param string $name 不正なキー名
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGetFail
     */
    public function testGetFail(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = $this->createTestObject();
        $obj->get($name);
    }

    /**
     * testGetFail() のための不正なキー名のテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetFail(): array
    {
        return [
            [""],
            ["this/is/invalid"],
        ];
    }

    /**
     * 指定されたキー名で正しい設定値が取得できることを確認します。
     *
     * @param string $name 取得するキー名
     * @param mixed $expected 期待される設定値
     * @covers ::__construct
     * @covers ::get
     * @covers ::<private>
     * @dataProvider provideTestGet
     */
    public function testGet(string $name, $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->get($name));
    }

    /**
     * testGet() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGet(): array
    {
        return [
            ["key1", 123],
            ["key2", ["aaa" => "hoge", "bbb" => "fuga"]],
            ["key2.aaa", "hoge"],
            ["key3.ccc.x", true],
            ["key3.ddd.y", null],
            ["key4", null],
        ];
    }

    /**
     * 指定されたキー名が存在するかどうかを正しく判定できることを確認します。
     *
     * @param string $name 確認するキー名
     * @param bool $expected 期待される判定結果
     * @covers ::__construct
     * @covers ::contains
     * @covers ::<private>
     * @dataProvider provideTestContains
     */
    public function testContains(string $name, bool $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->contains($name));
    }

    /**
     * testContains() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestContains(): array
    {
        return [
            ["key1", true],
            ["key9", false],
            ["key2.aaa", true],
            ["key2.ccc", false],
            ["key3.ccc.x", true],
            ["key3.ddd.x", false],
        ];
    }
}
