<?php

namespace Woof\Util;

use PHPUnit\Framework\TestCase;

/**
 * JsonDecoder のテストです。
 *
 * このテストクラスでは、不正な文字列をパースした際に発生する PHP の警告 (Warning) を抑制するため、
 * setUp() で一時的に error_reporting を 0 に変更しています。
 *
 * @coversDefaultClass Woof\Util\JsonDecoder
 */
class JsonDecoderTest extends TestCase
{
    /**
     * テスト実行前の元の error_reporting 設定を保持します。
     *
     * @var int
     */
    private $errorReporting;

    /**
     * テスト実行中の Warning 出力を抑制します。
     */
    public function setUp(): void
    {
        $this->errorReporting = error_reporting(0);
    }

    /**
     * 変更した error_reporting の設定を元の状態に戻します。
     */
    public function tearDown(): void
    {
        error_reporting($this->errorReporting);
    }

    /**
     * 同一のインスタンスが返されることを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstance(): void
    {
        $obj1 = JsonDecoder::getInstance();
        $obj2 = JsonDecoder::getInstance();
        $this->assertInstanceOf(JsonDecoder::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }

    /**
     * JSON 形式の文字列が正しく配列に変換されることと、不正な文字列や配列以外の場合は空配列が返されることを確認します。
     *
     * @param string $src パース対象の文字列
     * @param array $expected 期待される配列
     * @covers ::getInstance
     * @covers ::parse
     * @dataProvider provideTestParse
     */
    public function testParse(string $src, array $expected): void
    {
        $obj = JsonDecoder::getInstance();
        $this->assertSame($expected, $obj->parse($src));
    }

    /**
     * testParse() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParse(): array
    {
        return [
            ['{"foo": 1, "bar": "xxx"}', ["foo" => 1, "bar" => "xxx"]],
            ['"asdf"', []],
            ['{invalid}', []],
        ];
    }
}
