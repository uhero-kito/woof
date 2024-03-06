<?php

namespace Woof\Web\Session;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Session\ParserContext
 */
class ParserContextTest extends TestCase
{
    /**
     * さまざまな型のシリアライズ文字列が正しく連想配列に復元されることを確認します。
     *
     * @param string $source テスト対象のシリアライズ文字列
     * @param array $expected 期待される復元後の連想配列
     * @covers ::__construct
     * @covers ::parse
     * @covers ::<private>
     * @dataProvider provideTestParse
     */
    public function testParse(string $source, array $expected)
    {
        $obj = new ParserContext($source);
        $this->assertSame($expected, $obj->parse());
    }

    /**
     * testParse() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParse()
    {
        return [
            ["a|N;", ["a" => null]],
            ["a|b:0;", ["a" => false]],
            ["a|b:1;", ["a" => true]],
            ["a|i:123;", ["a" => 123]],
            ["a|d:-1.125;", ["a" => -1.125]],
            ["a|s:13:\"This is a pen\";", ["a" => "This is a pen"]],
        ];
    }

    /**
     * 配列を含む複雑なシリアライズ文字列が正しく復元されることを確認します。
     *
     * @covers ::__construct
     * @covers ::parse
     * @covers ::<private>
     */
    public function testParseArray()
    {
        $expected = [
            "a" => ["x", "yy", "zzz"],
            "b" => ["hoge" => 12, "fuga" => 345],
        ];
        $source   = 'a|a:3:{i:0;s:1:"x";i:1;s:2:"yy";i:2;s:3:"zzz";}b|a:2:{s:4:"hoge";i:12;s:4:"fuga";i:345;}';
        $obj      = new ParserContext($source);
        $this->assertSame($expected, $obj->parse());
    }

    /**
     * 不正なフォーマットの文字列を渡した場合に ParseException がスローされることを確認します。
     *
     * @param string $source 不正なフォーマットの文字列
     * @covers ::parse
     * @covers ::<private>
     * @dataProvider provideTestParseByInvalidFormat
     */
    public function testParseByInvalidFormat(string $source): void
    {
        $this->expectException(ParseException::class);
        $obj = new ParserContext($source);
        $obj->parse();
    }

    /**
     * testParseByInvalidFormat() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParseByInvalidFormat(): array
    {
        return [
            ["hoge;fuga;"],
            ["hoge|fuga;"],
            ["hoge|s:10:hogehoge"],
        ];
    }
}
