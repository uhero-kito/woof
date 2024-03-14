<?php

namespace Woof\Util;

use PHPUnit\Framework\TestCase;

/**
 * IniDecoder のテストです。
 *
 * このテストクラスでは、不正な文字列をパースした際に発生する PHP の警告 (Warning) を抑制するため、
 * setUp() で一時的に error_reporting を 0 に変更しています。
 *
 * @coversDefaultClass Woof\Util\IniDecoder
 */
class IniDecoderTest extends TestCase
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
        $obj1 = IniDecoder::getInstance();
        $obj2 = IniDecoder::getInstance();
        $this->assertInstanceOf(IniDecoder::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }

    /**
     * INI 形式の文字列が正しく配列に変換されることと、不正な文字列の場合は空配列が返されることを確認します。
     *
     * @param string $src パース対象の文字列
     * @param array $expected 期待される配列
     * @covers ::getInstance
     * @covers ::parse
     * @dataProvider provideTestParse
     */
    public function testParse(string $src, array $expected): void
    {
        $obj = IniDecoder::getInstance();
        $this->assertSame($expected, $obj->parse($src));
    }

    /**
     * testParse() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParse(): array
    {
        $src1 = implode(PHP_EOL, ["foo = 42", "bar = 'xxxx'"]);
        $arr1 = ["foo" => 42, "bar" => "xxxx"];
        return [
            [$src1, $arr1],
            ["=", []],
        ];
    }
}
