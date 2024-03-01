<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;
use Woof\Log\LoggerBuilder;
use Woof\System\VariablesBuilder;

/**
 * @coversDefaultClass Woof\Http\RequestLoader
 */
class RequestLoaderTest extends TestCase
{
    /**
     * 設定された Logger が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getLogger
     */
    public function testGetLogger(): void
    {
        $logger = (new LoggerBuilder())->build();
        $obj    = new RequestLoader($logger);
        $this->assertSame($logger, $obj->getLogger());
    }

    /**
     * 設定された HeaderParser が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getHeaderParser
     */
    public function testGetHeaderParser(): void
    {
        $parser = new HeaderParser(["test01"], ["test02"]);
        $obj    = new RequestLoader(null, $parser);
        $this->assertSame($parser, $obj->getHeaderParser());
    }

    /**
     * Variables オブジェクトからサーバー環境や各種パラメータが正しく読み取られ、
     * 完全な Request オブジェクトが構築されることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     * @covers ::<private>
     */
    public function testLoad(): void
    {
        $server  = [
            "HTTP_HOST"              => "example.com",
            "HTTPS"                  => "on",
            "HTTP_REFERER"           => "https://example.com/foo/bar/index.php?mode=input",
            "HTTP_ACCEPT_LANGUAGE"   => "ja,en-US;q=0.9,en;q=0.8",
            "HTTP_IF_MODIFIED_SINCE" => "Thu, 18 Apr 2019 02:45:55 GMT",
            "HTTP_DATE"              => "invalid HTTP-date format",
            "HTTP_ACCEPT_ENCODING"   => "",
            "REQUEST_URI"            => "/foo/bar/index.php?mode=submit&token=abc123",
            "REQUEST_METHOD"         => "POST",
        ];
        $gets    = [
            "process" => "confirm",
            "token"   => "abcd1234",
        ];
        $posts   = [
            "name"     => "John",
            "category" => ["3", "5", "7"],
        ];
        $cookies = [
            "session_id" => "abcd1234",
            "ad_token"   => "9876asdf",
        ];
        $files   = [
            "etc1" => [
                "name"     => "sample01.png",
                "type"     => "image/png",
                "tmp_name" => "/var/tmp/asdf1234",
                "error"    => 0,
                "size"     => 12345,
            ],
            "etc2" => [
                "name"     => "test.log",
                "type"     => "text/plain",
                "tmp_name" => "/var/tmp/abcd9999",
                "error"    => 0,
                "size"     => 5678,
            ],
        ];

        $h1 = new TextField("Referer", "https://example.com/foo/bar/index.php?mode=input");
        $h2 = new QualityValues("Accept-Language", ["ja" => 1.0, "en-US" => 0.9, "en" => 0.8]);
        $h3 = new HttpDate("If-Modified-Since", 1555555555);
        $f1 = new UploadFile("sample01.png", "/var/tmp/asdf1234", 0, 12345);
        $var = (new VariablesBuilder())
            ->setServer($server)
            ->setGet($gets)
            ->setPost($posts)
            ->setCookie($cookies)
            ->setFiles($files)
            ->build();
        $obj = new RequestLoader();
        $req = $obj->load($var);
        $this->assertFalse($req->hasHeader("date"));
        $this->assertFalse($req->hasHeader("accept-encoding"));
        $this->assertEquals($h1, $req->getHeader("REFERER"));
        $this->assertEquals($h2, $req->getHeader("Accept-language"));
        $this->assertEquals($h3, $req->getHeader("if-modified-since"));
        $this->assertEquals($f1, $req->getUploadFile("etc1"));
        $this->assertSame("example.com", $req->getHost());
        $this->assertSame("/foo/bar/index.php?mode=submit&token=abc123", $req->getUri());
        $this->assertSame("/foo/bar/index.php", $req->getPath());
        $this->assertSame("https", $req->getScheme());
        $this->assertSame("post", $req->getMethod());
        $this->assertSame("confirm", $req->getQuery("process"));
        $this->assertSame(["3", "5", "7"], $req->getPost("category"));
        $this->assertSame("abcd1234", $req->getCookie("session_id"));
    }
}
