<?php

namespace Woof\Web;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Session
 */
class SessionTest extends TestCase
{
    /**
     * 不正な形式のセッション ID を指定してインスタンスを生成しようとした場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Session("invalid id format", []);
    }

    /**
     * セッション ID が有効な形式 (英数字・ハイフン・カンマのみ) であるか正しく判定されることを確認します。
     *
     * @param string $id 判定対象のセッション ID
     * @param bool $expected 期待される判定結果
     * @dataProvider provideTestValidateId
     * @covers ::validateId
     */
    public function testValidateId(string $id, bool $expected): void
    {
        $this->assertSame($expected, Session::validateId($id));
    }

    /**
     * testValidateId() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestValidateId(): array
    {
        return [
            ["1234567890abcdef", true],
            ["This-is-a-pen", true],
            ["", false],
            ["this/is/invalid", false],
        ];
    }

    /**
     * 設定されたセッション ID が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getId
     */
    public function testGetId(): void
    {
        $obj = new Session("1234567890abcdef", []);
        $this->assertSame("1234567890abcdef", $obj->getId());
    }

    /**
     * セッションデータの設定・取得、および未設定時に代替値が返されることを確認します。
     *
     * @covers ::set
     * @covers ::get
     */
    public function testSetAndGet(): void
    {
        $data = [
            "hoge" => 1,
            "fuga" => "xxxx",
        ];
        $obj = new Session("1234567890abcdef", $data);
        $this->assertSame("xxxx", $obj->get("fuga"));
        $this->assertSame("xxxx", $obj->get("fuga", "abc"));
        $this->assertNull($obj->get("piyo"));
        $this->assertSame("abc", $obj->get("piyo", "abc"));

        $obj->set("hoge", 234);
        $obj->set("hige", "xyz");
        $this->assertSame(234, $obj->get("hoge"));
        $this->assertSame("xyz", $obj->get("hige"));
    }

    /**
     * すべてのセッションデータが連想配列として正しく取得できることを確認します。
     *
     * @covers ::getAll
     */
    public function testGetAll(): void
    {
        $data = [
            "hoge" => 1,
            "fuga" => "xxxx",
        ];
        $expected = [
            "hoge" => 234,
            "fuga" => "xxxx",
            "hige" => "xyz",
        ];
        $obj = new Session("1234567890abcdef", $data);
        $obj->set("hoge", 234);
        $obj->set("hige", "xyz");
        $this->assertSame($expected, $obj->getAll());
    }

    /**
     * 新規作成フラグ (isNew) が正しく設定および取得できることを確認します。
     *
     * @covers ::isNew
     */
    public function testIsNew(): void
    {
        $obj1 = new Session("1234567890abcdef", []);
        $obj2 = new Session("1234567890abcdef", [], false);
        $obj3 = new Session("1234567890abcdef", [], true);
        $this->assertFalse($obj1->isNew());
        $this->assertFalse($obj2->isNew());
        $this->assertTrue($obj3->isNew());
    }

    /**
     * データが変更された際に isChanged フラグが true になることを確認します。
     *
     * @covers ::isChanged
     */
    public function testIsChanged(): void
    {
        $data = [
            "hoge" => 1,
            "fuga" => "xxxx",
        ];
        $obj = new Session("1234567890abcdef", $data);
        $this->assertFalse($obj->isChanged());
        $obj->set("hoge", 234);
        $this->assertTrue($obj->isChanged());
    }

    /**
     * セッションデータが空であるかが正しく判定されることを確認します。
     *
     * @covers ::isEmpty
     */
    public function testIsEmpty(): void
    {
        $data = [
            "hoge" => 1,
            "fuga" => "xxxx",
        ];
        $obj1 = new Session("1234567890abcdef", []);
        $obj2 = new Session("1234567890abcdef", $data);

        $this->assertTrue($obj1->isEmpty());
        $this->assertFalse($obj2->isEmpty());
    }
}
