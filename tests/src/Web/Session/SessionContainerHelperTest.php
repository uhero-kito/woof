<?php

namespace Woof\Web\Session;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Session\SessionContainerHelper
 */
class SessionContainerHelperTest extends TestCase
{
    /**
     * セッションデータが指定したフォーマットで正しくシリアライズされることを確認します。
     *
     * @param array $data シリアライズ対象の配列
     * @param string $expected 期待される文字列
     * @covers ::serialize
     * @dataProvider provideTestSerialize
     */
    public function testSerialize(array $data, string $expected): void
    {
        $obj = new SessionContainerHelper();
        $this->assertSame($expected, $obj->serialize($data));
    }

    /**
     * testSerialize() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestSerialize(): array
    {
        return [
            [["hoge" => 456, "fuga" => "asdf", "piyo" => true], 'hoge|i:456;fuga|s:4:"asdf";piyo|b:1;'],
            [[], ""],
        ];
    }

    /**
     * キーの一覧から "sess_" で始まるものだけが正しく抽出されることを確認します。
     *
     * @covers ::filterSessionKeys
     */
    public function testFilterSessionKeys(): void
    {
        $obj   = new SessionContainerHelper();
        $input = [
            "sess_1234567890abcdef",
            "sess_1357924680bbbbbb",
            "dummy_file.txt",
            "sessions/sess_9876543210aaaaaa",
            "sessions/not_sess_file",
        ];

        $generator = $obj->filterSessionKeys($input);
        $result    = iterator_to_array($generator, false);
        $expected  = [
            "sess_1234567890abcdef",
            "sess_1357924680bbbbbb",
            "sessions/sess_9876543210aaaaaa",
        ];
        $this->assertSame($expected, $result);
    }
}
