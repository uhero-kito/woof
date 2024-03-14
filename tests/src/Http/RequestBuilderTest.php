<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\RequestBuilder
 */
class RequestBuilderTest extends TestCase
{
    /**
     * ホスト名の設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setHost
     * @covers ::getHost
     */
    public function testSetHostAndGetHost(): void
    {
        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setHost("www.example.com"));
        $this->assertSame("www.example.com", $obj->getHost());
    }

    /**
     * URI の設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setUri
     * @covers ::getUri
     */
    public function testSetUriAndGetUri(): void
    {
        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setUri("/hoge/index.html?aaa=1"));
        $this->assertSame("/hoge/index.html?aaa=1", $obj->getUri());
    }

    /**
     * パスの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setPath
     * @covers ::getPath
     */
    public function testSetPathAndGetPath(): void
    {
        $obj = new RequestBuilder();
        $obj->setPath("/hoge/index.html");
        $this->assertSame("/hoge/index.html", $obj->getPath());
    }

    /**
     * スキームの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setScheme
     * @covers ::getScheme
     */
    public function testSetSchemeAndGetScheme(): void
    {
        $obj = new RequestBuilder();
        $obj->setScheme("https");
        $this->assertSame("https", $obj->getScheme());
    }

    /**
     * HTTP メソッドの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setMethod
     * @covers ::getMethod
     */
    public function testSetMethodAndGetMethod(): void
    {
        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setMethod("post"));
        $this->assertSame("post", $obj->getMethod());
    }

    /**
     * ヘッダーの設定と取得・セットされた EmptyField が無視されること・同名ヘッダーの上書きが正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setHeader
     * @covers ::getHeaderList
     */
    public function testSetHeaderAndGetHeaderList(): void
    {
        $h1       = new TextField("X-HEADER-TEST01", "hoge");
        $h2       = new TextField("x-header-test02", "fuga");
        $h3       = new TextField("X-Header-Test01", "piyo");
        $expected = [
            "x-header-test01" => $h3,
            "x-header-test02" => $h2,
        ];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setHeader($h1)->setHeader($h2)->setHeader($h3)->setHeader(EmptyField::getInstance()));
        $this->assertEquals($expected, $obj->getHeaderList());
    }

    /**
     * 単一の GET パラメータの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setQuery
     * @covers ::getQueryList
     */
    public function testSetQueryAndGetQueryList(): void
    {
        $expected = [
            "q"  => "sample",
            "id" => "123",
        ];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setQuery("q", "sample")->setQuery("id", "123"));
        $this->assertSame($expected, $obj->getQueryList());
    }

    /**
     * 配列による GET パラメータの一括設定 (マージ) が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setQueryList
     * @covers ::getQueryList
     */
    public function testSetQueryListAndGetQueryList(): void
    {
        $arr1     = [
            "a" => "xxxx",
            "b" => "yyyy",
        ];
        $arr2     = [
            "b" => "yyy",
            "c" => "zzz",
        ];
        $expected = [
            "a" => "xxxx",
            "b" => "yyy",
            "c" => "zzz",
        ];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setQueryList($arr1)->setQueryList($arr2));
        $this->assertSame($expected, $obj->getQueryList());
    }

    /**
     * 単一の POST パラメータの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setPost
     * @covers ::getPostList
     */
    public function testSetPostAndGetPostList(): void
    {
        $expected = [
            "category" => ["1", "4", "9"],
            "search"   => "sample text",
        ];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setPost("category", ["1", "4", "9"])->setPost("search", "sample text"));
        $this->assertSame($expected, $obj->getPostList());
    }

    /**
     * 配列による POST パラメータの一括設定 (マージ) が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setPostList
     * @covers ::getPostList
     */
    public function testSetPostListAndGetPostList(): void
    {
        $arr1     = [
            "a" => "xxxx",
            "b" => "yyyy",
        ];
        $arr2     = [
            "b" => "yyy",
            "c" => "zzz",
        ];
        $expected = [
            "a" => "xxxx",
            "b" => "yyy",
            "c" => "zzz",
        ];

        $obj = new RequestBuilder();
        $obj->setPostList($arr1);
        $obj->setPostList($arr2);
        $this->assertSame($expected, $obj->getPostList());
    }

    /**
     * 単一の Cookie の設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setCookie
     * @covers ::getCookieList
     */
    public function testSetCookieAndGetCookieList(): void
    {
        $obj = new RequestBuilder();
        $obj->setCookie("name", "John");
        $obj->setCookie("token", "abcd1234");
        $this->assertSame(["name" => "John", "token" => "abcd1234"], $obj->getCookieList());
    }

    /**
     * 配列による Cookie の一括設定 (マージ) が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setCookieList
     * @covers ::getCookieList
     */
    public function testSetCookieListAndGetCookieList(): void
    {
        $arr1     = [
            "a" => "xxxx",
            "b" => "yyyy",
        ];
        $arr2     = [
            "b" => "yyy",
            "c" => "zzz",
        ];
        $expected = [
            "a" => "xxxx",
            "b" => "yyy",
            "c" => "zzz",
        ];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setCookieList($arr1)->setCookieList($arr2));
        $this->assertSame($expected, $obj->getCookieList());
    }

    /**
     * 添付ファイルの設定と取得が正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::setUploadFile
     * @covers ::getUploadFileList
     */
    public function testSetUploadFileAndGetUploadFileList(): void
    {
        $f1       = new UploadFile("tmp.zip", TEST_DATA_DIR . "/tmp.zip", 0, 1234);
        $f2       = new UploadFile("sample.txt", TEST_DATA_DIR . "/sample.txt", 0, 789);
        $expected = ["foo" => $f1, "bar" => $f2];

        $obj = new RequestBuilder();
        $this->assertSame($obj, $obj->setUploadFile("foo", $f1)->setUploadFile("bar", $f2));
        $this->assertSame($expected, $obj->getUploadFileList());
    }

    /**
     * 必要な情報が設定された状態で、正しく Request オブジェクトが生成されることを確認します。
     *
     * @covers ::__construct
     * @covers ::build
     */
    public function testBuild(): void
    {
        $obj = new RequestBuilder();
        $obj->setHost("example.com");
        $this->assertInstanceOf(Request::class, $obj->build());
    }

    /**
     * 既存の Request オブジェクトをコンストラクタに渡すことで、その状態が正しくインポートされることを確認します。
     *
     * @covers ::__construct
     * @covers ::importRequest
     */
    public function testConstructByRequest(): void
    {
        $obj1 = (new RequestBuilder())
            ->setHost("www.example.com")
            ->setUri("/foo/bar?a=1&b=2")
            ->setPath("/foo/bar")
            ->setMethod("post")
            ->setScheme("https")
            ->setQueryList(["a" => "1", "b" => "2"])
            ->setPostList(["content" => "This is test"])
            ->setCookieList(["session_id" => "aaaa1234"])
            ->setHeader(new HttpDate("If-Modified-Since", 1555555555))
            ->setUploadFile("img", new UploadFile("sample.png", "/tmp/sample.png", 0, 2468));
        $req  = $obj1->build();
        $obj2 = new RequestBuilder($req);
        $this->assertEquals($obj2, $obj1);
    }
}
