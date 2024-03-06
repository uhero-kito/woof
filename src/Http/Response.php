<?php

namespace Woof\Http;

use Woof\Http\Response\Body;
use Woof\Http\Response\Cookie;
use Woof\Http\Response\EmptyBody;

/**
 * HTTP レスポンス全体 (レスポンスボディ, ステータス, ヘッダー, Cookie) の情報を保持するデータクラスです。
 */
class Response
{
    /**
     * レスポンスのボディ (コンテンツ) を保持するオブジェクトです。
     *
     * @var Body
     */
    private $body;

    /**
     * HTTP ステータスをあらわすオブジェクトです。
     *
     * @var Status
     */
    private $status;

    /**
     * ヘッダー名 (小文字) をキーとした HeaderField オブジェクトの連想配列です。
     *
     * @var HeaderField[]
     */
    private $headerList;

    /**
     * Cookie の名前をキーとした、Cookie オブジェクトの連想配列です。
     *
     * @var Cookie[]
     */
    private $cookieList;

    /**
     * このクラスは ResponseBuilder を使用して初期化します。
     * 外部から直接インスタンス化することはできません。
     */
    private function __construct()
    {
        $this->headerList = [];
        $this->cookieList = [];
    }

    /**
     * ResponseBuilder の状態を元に、新しい Response インスタンスを生成します。
     *
     * Body が設定されている場合は、自動的に Content-Type と Content-Length ヘッダーが付与されます。
     * このメソッドは ResponseBuilder::build() から参照されます。
     *
     * @param ResponseBuilder $builder 構築済みのビルダーオブジェクト
     * @return Response 生成された Response オブジェクト
     * @ignore
     */
    public static function newInstance(ResponseBuilder $builder): self
    {
        $body       = $builder->getBody();
        $headerList = $builder->getHeaderList();
        if ($body !== EmptyBody::getInstance()) {
            $headerList["content-type"]   = new TextField("Content-Type", $body->getContentType());
            $headerList["content-length"] = new TextField("Content-Length", (string) $body->getContentLength());
        }

        $res             = new Response();
        $res->body       = $body;
        $res->status     = $builder->getStatus();
        $res->headerList = $headerList;
        $res->cookieList = $builder->getCookieList();
        return $res;
    }

    /**
     * レスポンスボディを取得します。
     *
     * @return Body レスポンスボディをあらわす Body オブジェクト
     */
    public function getBody(): Body
    {
        return $this->body;
    }

    /**
     * HTTP ステータスを取得します。
     *
     * @return Status HTTP ステータスをあらわす Status オブジェクト
     */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * 指定された名前のヘッダーが存在するかどうか調べます。
     * ヘッダー名の大文字・小文字は区別されません。
     *
     * @param string $name ヘッダー名
     * @return bool 存在する場合のみ true
     */
    public function hasHeader(string $name): bool
    {
        $key = strtolower($name);
        return array_key_exists($key, $this->headerList);
    }

    /**
     * 指定された名前のヘッダーを返します。
     * もしも指定されたヘッダーが存在しない場合は EmptyField を返します。
     * ヘッダー名の大文字・小文字は区別されません。
     *
     * @param string $name 取得したいヘッダー名
     * @return HeaderField ヘッダー情報を表現する HeaderField オブジェクト (存在しない場合は EmptyField)
     */
    public function getHeader(string $name): HeaderField
    {
        $key = strtolower($name);
        return $this->headerList[$key] ?? EmptyField::getInstance();
    }

    /**
     * 設定されているすべてのヘッダーフィールドの配列を取得します。
     *
     * @return HeaderField[] HeaderField オブジェクトの配列 (インデックス配列)
     */
    public function getHeaderList(): array
    {
        return array_values($this->headerList);
    }

    /**
     * 設定されているすべての Cookie を取得します。
     *
     * @return Cookie[] Cookie 名をキーとした連想配列
     */
    public function getCookieList(): array
    {
        return $this->cookieList;
    }
}
