<?php

namespace Woof\Http\Response;

use Woof\Util\DataObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Response\JsonBody
 */
class JsonBodyTest extends TestCase
{
    /**
     * テスト用の JsonBody インスタンスを生成して返します。
     *
     * @return JsonBody テスト用のインスタンス
     */
    private function getTestObject(): JsonBody
    {
        $data    = [
            "str"    => "Hello / World",
            "list"   => [3, 5, 7, true, false],
            "object" => [
                "aaa" => "foo",
                "bbb" => "bar",
                "ccc" => "baz",
            ],
        ];
        $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        return new JsonBody($data, $options);
    }

    /**
     * エンコード結果として期待される JSON 文字列を生成して返します。
     *
     * @return string 期待される JSON 文字列
     */
    private function getExpectedOutput(): string
    {
        return implode("\n", [
            '{',
            '    "str": "Hello / World",',
            '    "list": [',
            '        3,',
            '        5,',
            '        7,',
            '        true,',
            '        false',
            '    ],',
            '    "object": {',
            '        "aaa": "foo",',
            '        "bbb": "bar",',
            '        "ccc": "baz"',
            '    }',
            '}',
        ]);
    }

    /**
     * コンストラクタに配列を渡した場合に、正しく DataObject に変換して保持されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $obj      = $this->getTestObject();
        $expected = [
            "str"    => "Hello / World",
            "list"   => [3, 5, 7, true, false],
            "object" => [
                "aaa" => "foo",
                "bbb" => "bar",
                "ccc" => "baz",
            ],
        ];
        $data = $obj->getData();
        $this->assertSame($expected, $data->toValue());
    }

    /**
     * コンストラクタに DataObject を渡した場合に、そのまま保持されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getData
     */
    public function testGetDataByDataObject(): void
    {
        $data = new class implements DataObject {
            public function toValue()
            {
                return ["key" => "test"];
            }
        };
        $obj = new JsonBody($data);
        $this->assertSame($data, $obj->getData());
    }

    /**
     * 設定したエンコードオプションが正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getEncodeOptions
     */
    public function testGetEncodeOptions(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame(192, $obj->getEncodeOptions());
    }

    /**
     * エンコードされた JSON 文字列が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOutput
     */
    public function testGetOutput(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame($this->getExpectedOutput(), $obj->getOutput());
    }

    /**
     * エンコードされた JSON 文字列が正しく出力され、true が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::sendOutput
     */
    public function testSendOutput(): void
    {
        $this->expectOutputString($this->getExpectedOutput());
        $obj = $this->getTestObject();
        $this->assertTrue($obj->sendOutput());
    }

    /**
     * Content-Type として application/json が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentType
     */
    public function testGetContentType(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame("application/json", $obj->getContentType());
    }

    /**
     * エンコードされた JSON 文字列のバイト数が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentLength
     */
    public function testGetContentLength(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame(200, $obj->getContentLength());
    }
}
