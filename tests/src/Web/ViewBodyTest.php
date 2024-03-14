<?php

namespace Woof\Web;

use PHPUnit\Framework\TestCase;
use Woof\FileResources;
use Woof\Resources;

/**
 * @coversDefaultClass Woof\Web\ViewBody
 */
class ViewBodyTest extends TestCase
{
    /**
     * テストデータが配置されるディレクトリのパスです。
     *
     * @var string
     */
    const TEST_DIR = TEST_DATA_DIR . "/Web/ViewBody/subjects";

    /**
     * テスト用の ViewBody インスタンスを生成して返します。
     *
     * @return ViewBody テスト用のインスタンス
     */
    private function getTestObject(): ViewBody
    {
        return new ViewBody(new ViewBodyTest_TestView(), new FileResources(self::TEST_DIR), new Context("/base"));
    }

    /**
     * テストデータの文字列を取得します。
     *
     * @return string ファイルの内容
     */
    private function getTestData(): string
    {
        return file_get_contents(self::TEST_DIR . "/sample.txt");
    }

    /**
     * 保持している View オブジェクトが正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getView
     */
    public function testGetView(): void
    {
        $this->assertEquals(new ViewBodyTest_TestView(), $this->getTestObject()->getView());
    }

    /**
     * レンダリング結果の文字長が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentLength
     */
    public function testGetContentLength(): void
    {
        $this->assertSame(strlen($this->getTestData()), $this->getTestObject()->getContentLength());
    }

    /**
     * View オブジェクトに設定された Content-Type が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentType
     */
    public function testGetContentType(): void
    {
        $this->assertSame("text/plain", $this->getTestObject()->getContentType());
    }

    /**
     * レンダリング結果の文字列が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOutput
     */
    public function testGetOutput(): void
    {
        $this->assertSame($this->getTestData(), $this->getTestObject()->getOutput());
    }

    /**
     * レンダリング結果が正しく出力され、true が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::sendOutput
     */
    public function testSendOutput(): void
    {
        $this->expectOutputString($this->getTestData());
        $this->assertTrue($this->getTestObject()->sendOutput());
    }
}

/**
 * ViewBodyTest で使用するためのダミーの View 実装クラスです。
 */
class ViewBodyTest_TestView implements View
{
    /**
     * ダミーの Content-Type を返します。
     *
     * @return string "text/plain"
     */
    public function getContentType(): string
    {
        return "text/plain";
    }

    /**
     * 引数のリソースから sample.txt を読み込んで返します。
     *
     * @param Resources $resources リソースオブジェクト
     * @param Context $context コンテキストオブジェクト
     * @return string 読み込まれた文字列
     */
    public function render(Resources $resources, Context $context): string
    {
        return $resources->get("sample.txt");
    }
}
