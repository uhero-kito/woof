<?php

namespace Woof\Http;

/**
 * HTTP の各種応答ステータス (ステータスコードおよび reason-phrase) をあらわすクラスです。
 */
class Status
{
    /**
     * "200" や "404" など、3 桁の数字から成るステータスコードです。
     *
     * @var string
     */
    private $statusCode;

    /**
     * "OK" や "Not Found" など、ステータスの内容をあらわすテキスト (reason-phrase) です。
     *
     * @var string
     */
    private $reasonPhrase;

    /**
     * 指定されたステータスコードおよび文言を持つ Status オブジェクトを生成します。
     *
     * @param string $statusCode 3桁のステータスコード
     * @param string $reasonPhrase ステータスをあらわすテキスト
     */
    public function __construct(string $statusCode, string $reasonPhrase)
    {
        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * HTTP レスポンスのステータスラインとして出力可能な形式に書式化します。
     *
     * @return string ステータスライン ("HTTP/1.1 200 OK" など)
     */
    public function format(): string
    {
        return "HTTP/1.1 {$this->statusCode} {$this->reasonPhrase}";
    }

    /**
     * 正常終了 (200 OK) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 200 OK の Status オブジェクト
     */
    public static function getOK(): self
    {
        return new self("200", "OK");
    }

    /**
     * 指定された URL が恒久的に移動されたこと (301 Moved Permanently) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 301 Moved Permanently の Status オブジェクト
     */
    public static function get301(): self
    {
        return new self("301", "Moved Permanently");
    }

    /**
     * POST データの処理後などに一時的なリダイレクトを行うため (302 Found) の Status インスタンスを生成して返します。
     *
     * @return Status 302 Found の Status オブジェクト
     */
    public static function get302(): self
    {
        return new self("302", "Found");
    }

    /**
     * 指定された URL について、最後にクライアントに送信してから変更がないこと (304 Not Modified) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 304 Not Modified の Status オブジェクト
     */
    public static function get304(): self
    {
        return new self("304", "Not Modified");
    }

    /**
     * 受け取った HTTP リクエストが不正であること (400 Bad Request) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 400 Bad Request の Status オブジェクト
     */
    public static function get400(): self
    {
        return new self("400", "Bad Request");
    }

    /**
     * 指定された URL へのアクセスに認証が必要であること (401 Unauthorized) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 401 Unauthorized の Status オブジェクト
     */
    public static function get401(): self
    {
        return new self("401", "Unauthorized");
    }

    /**
     * 指定された URL へのアクセス権限がないこと (403 Forbidden) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 403 Forbidden の Status オブジェクト
     */
    public static function get403(): self
    {
        return new self("403", "Forbidden");
    }

    /**
     * 指定された URL (リソース) が存在しないこと (404 File Not Found) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 404 File Not Found の Status オブジェクト
     */
    public static function get404(): self
    {
        return new self("404", "File Not Found");
    }

    /**
     * サーバー側で何らかのエラーが発生したこと (500 Internal Server Error) をあらわす Status インスタンスを生成して返します。
     *
     * @return Status 500 Internal Server Error の Status オブジェクト
     */
    public static function get500(): self
    {
        return new self("500", "Internal Server Error");
    }
}
