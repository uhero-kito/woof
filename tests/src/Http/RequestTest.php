<?php

namespace Woof\Http;

use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Request
 */
class RequestTest extends TestCase
{
    /**
     * テスト用の RequestBuilder を生成して返します。
     *
     * @return RequestBuilder テスト用の RequestBuilder インスタンス
     */
    private function createTestBuilder(): RequestBuilder
    {
        $builder = new RequestBuilder();
        $builder->setHost("www.example.com");
        return $builder;
    }

    /**
     * ホスト名が設定されていない状態でインスタンスを生成した場合に LogicException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     */
    public function testNewInstanceFail(): void
    {
        $this->expectException(LogicException::class);
        $builder = new RequestBuilder();
        $builder->build();
    }

    /**
     * 設定されたホスト名が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getHost
     */
    public function testGetHost(): void
    {
        $builder = new RequestBuilder();

        $obj1 = $builder->setHost("www.example.jp")->build();
        $this->assertSame("www.example.jp", $obj1->getHost());
    }

    /**
     * 設定された URI が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getUri
     */
    public function testGetUri(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->build();
        $this->assertSame("", $obj1->getUri());
        $obj2 = $builder->setUri("/hoge/index.html?aaa=1")->build();
        $this->assertSame("/hoge/index.html?aaa=1", $obj2->getUri());
    }

    /**
     * 設定されたパス (クエリを含まない URI) が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getPath
     */
    public function testGetPath(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->build();
        $this->assertSame("", $obj1->getPath());
        $obj2 = $builder->setPath("/hoge/index.html")->build();
        $this->assertSame("/hoge/index.html", $obj2->getPath());
    }

    /**
     * 設定されたスキームが正しく取得・小文字化されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getScheme
     */
    public function testGetScheme(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->build();
        $this->assertSame("http", $obj1->getScheme());
        $obj2 = $builder->setScheme("HTTPS")->build();
        $this->assertSame("https", $obj2->getScheme());
    }

    /**
     * 設定された HTTP メソッドが正しく取得・小文字化されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getMethod
     */
    public function testGetMethod(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->build();
        $this->assertSame("get", $obj1->getMethod());
        $obj2 = $builder->setMethod("POST")->build();
        $this->assertSame("post", $obj2->getMethod());
    }

    /**
     * ヘッダーの存在確認が、大文字・小文字を区別せずに行えることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::hasHeader
     */
    public function testHasHeader(): void
    {
        $builder = $this->createTestBuilder();

        $h1   = new TextField("X-TESTHEADER", "hogehoge");
        $obj1 = $builder->build();
        $this->assertFalse($obj1->hasHeader("X-TestHeader"));
        $obj2 = $builder->setHeader($h1)->build();
        $this->assertTrue($obj2->hasHeader("X-TestHeader"));
    }

    /**
     * 指定したヘッダーが正しく取得できること、存在しない場合は EmptyField が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getHeader
     */
    public function testGetHeader(): void
    {
        $builder = $this->createTestBuilder();

        $h1   = new TextField("X-testheader", "hogehoge");
        $obj1 = $builder->build();
        $this->assertSame(EmptyField::getInstance(), $obj1->getHeader("X-TestHeader"));
        $obj2 = $builder->setHeader($h1)->build();
        $this->assertSame($h1, $obj2->getHeader("X-TestHeader"));
    }

    /**
     * 設定されたすべてのヘッダーが配列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getHeaderList
     */
    public function testGetHeaderList(): void
    {
        $builder = $this->createTestBuilder();

        $h1   = new TextField("X-Sample-Test01", "hogehoge");
        $h2   = new TextField("X-Sample-Test02", "fugafuga");
        $obj1 = $builder->build();
        $this->assertSame([], $obj1->getHeaderList());
        $obj2 = $builder->setHeader($h1)->setHeader($h2)->build();
        $this->assertSame([$h1, $h2], $obj2->getHeaderList());
    }

    /**
     * 指定した GET パラメータが正しく取得できること、存在しない場合は null が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getQuery
     */
    public function testGetQuery(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->setQuery("hoge", "asdf")->build();
        $this->assertNull($obj1->getQuery("aaaa"));
        $this->assertSame("asdf", $obj1->getQuery("hoge"));
    }

    /**
     * 設定されたすべての GET パラメータが配列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getQueryList
     */
    public function testGetQueryList(): void
    {
        $builder = $this->createTestBuilder();

        $arr  = [
            "search" => "test",
            "mode"   => "1",
        ];
        $obj1 = $builder->setQueryList($arr)->build();
        $this->assertSame($arr, $obj1->getQueryList());
    }

    /**
     * 指定した POST パラメータが正しく取得できることと、存在しない場合は null が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getPost
     */
    public function testGetPost(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->setPost("fuga", "xxxx")->build();
        $this->assertNull($obj1->getPost("abcd"));
        $this->assertSame("xxxx", $obj1->getPost("fuga"));
    }

    /**
     * 設定されたすべての POST パラメータが配列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getPostList
     */
    public function testGetPostList(): void
    {
        $builder = $this->createTestBuilder();

        $arr  = [
            "content" => "This is a pen.",
            "process" => "confirm",
        ];
        $obj1 = $builder->setPostList($arr)->build();
        $this->assertSame($arr, $obj1->getPostList());
    }

    /**
     * 指定した Cookie が正しく取得できることと、存在しない場合は null が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getCookie
     */
    public function testGetCookie(): void
    {
        $builder = $this->createTestBuilder();

        $obj1 = $builder->setCookie("piyo", "yyyy")->build();
        $this->assertNull($obj1->getCookie("abcd"));
        $this->assertSame("yyyy", $obj1->getCookie("piyo"));
    }

    /**
     * 設定されたすべての Cookie が配列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getCookieList
     */
    public function testGetCookieList(): void
    {
        $builder = $this->createTestBuilder();

        $arr  = [
            "session_id" => "abcd1234",
            "ad_token"   => "aaaaaaaa",
        ];
        $obj1 = $builder->setCookieList($arr)->build();
        $this->assertSame($arr, $obj1->getCookieList());
    }

    /**
     * 指定したパラメータ名の添付ファイル (UploadFile) が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getUploadFile
     */
    public function testGetUploadFile(): void
    {
        $builder = $this->createTestBuilder();

        $file = new UploadFile("sample.zip", TEST_DATA_DIR . "/tmp.zip", 0, 1234);
        $obj1 = $builder->setUploadFile("tmp", $file)->build();
        $this->assertSame($file, $obj1->getUploadFile("tmp"));
    }

    /**
     * 指定したパラメータ名の添付ファイルが存在するかどうかが正しく判定できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::hasUploadFile
     */
    public function testHasUploadFile(): void
    {
        $builder = $this->createTestBuilder();

        $file = new UploadFile("tmp.zip", TEST_DATA_DIR . "/tmp.zip", 0, 1234);
        $obj1 = $builder->setUploadFile("tmp", $file)->build();
        $this->assertFalse($obj1->hasUploadFile("abc"));
        $this->assertTrue($obj1->hasUploadFile("tmp"));
    }

    /**
     * 存在しない添付ファイルを取得しようとした際に UploadFileNotFoundException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getUploadFile
     */
    public function testGetUploadFileFailWithFileNotFound(): void
    {
        $this->expectException(UploadFileNotFoundException::class);
        $builder = $this->createTestBuilder();

        $obj1 = $builder->build();
        $obj1->getUploadFile("notfound");
    }

    /**
     * 設定されたすべての添付ファイルが配列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getUploadFileList
     */
    public function testGetUploadFileList(): void
    {
        $builder = $this->createTestBuilder();

        $f1   = new UploadFile("sample.zip", TEST_DATA_DIR . "/sample.zip", 0, 1234);
        $f2   = new UploadFile("test01.png", TEST_DATA_DIR . "/test01.png", 0, 2345);
        $obj1 = $builder->setUploadFile("sample", $f1)->setUploadFile("test", $f2)->build();
        $this->assertSame(["sample" => $f1, "test" => $f2], $obj1->getUploadFileList());
    }
}
