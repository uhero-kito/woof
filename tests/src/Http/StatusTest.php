<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Status
 */
class StatusTest extends TestCase
{
    /**
     * ステータスラインが正しくフォーマットされることを確認します。
     *
     * @covers ::__construct
     * @covers ::format
     */
    public function testFormat()
    {
        $obj = new Status("500", "Internal Server Error");
        $this->assertSame("HTTP/1.1 500 Internal Server Error", $obj->format());
    }

    /**
     * 200 OK の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::getOK
     */
    public function testGetOK()
    {
        $expected = new Status("200", "OK");
        $this->assertEquals($expected, Status::getOK());
    }

    /**
     * 301 Moved Permanently の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get301
     */
    public function testGet301()
    {
        $expected = new Status("301", "Moved Permanently");
        $this->assertEquals($expected, Status::get301());
    }

    /**
     * 302 Found の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get302
     */
    public function testGet302()
    {
        $expected = new Status("302", "Found");
        $this->assertEquals($expected, Status::get302());
    }

    /**
     * 304 Not Modified の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get304
     */
    public function testGet304()
    {
        $expected = new Status("304", "Not Modified");
        $this->assertEquals($expected, Status::get304());
    }

    /**
     * 400 Bad Request の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get400
     */
    public function testGet400()
    {
        $expected = new Status("400", "Bad Request");
        $this->assertEquals($expected, Status::get400());
    }

    /**
     * 401 Unauthorized の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get401
     */
    public function testGet401()
    {
        $expected = new Status("401", "Unauthorized");
        $this->assertEquals($expected, Status::get401());
    }

    /**
     * 403 Forbidden の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get403
     */
    public function testGet403()
    {
        $expected = new Status("403", "Forbidden");
        $this->assertEquals($expected, Status::get403());
    }

    /**
     * 404 File Not Found の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get404
     */
    public function testGet404()
    {
        $expected = new Status("404", "File Not Found");
        $this->assertEquals($expected, Status::get404());
    }

    /**
     * 500 Internal Server Error の Status オブジェクトが正しく生成されることを確認します。
     *
     * @covers ::get500
     */
    public function testGet500()
    {
        $expected = new Status("500", "Internal Server Error");
        $this->assertEquals($expected, Status::get500());
    }
}
