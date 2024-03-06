<?php

namespace Woof\Http;

use Woof\Http\Response\Body;
use Woof\Http\Response\Cookie;
use Woof\Http\Response\CookieAttributes;
use Woof\Http\Response\EmptyBody;

/**
 * Response オブジェクトを構築するためのビルダークラスです。
 */
class ResponseBuilder
{
    /**
     * 設定するレスポンスボディです。
     *
     * @var Body
     */
    private $body;

    /**
     * 設定する HTTP ステータスです。
     *
     * @var Status
     */
    private $status;

    /**
     * 設定するヘッダーフィールドの連想配列です。ヘッダー名 (小文字) がキーとなります。
     *
     * @var HeaderField[]
     */
    private $headerList;

    /**
     * 設定する Cookie の連想配列です。Cookie 名がキーとなります。
     *
     * @var Cookie[]
     */
    private $cookieList;

    /**
     * 新しい ResponseBuilder インスタンスを生成します。
     * 引数に Response オブジェクトを渡すことで、既存のレスポンスの情報をコピーして初期化することができます。
     *
     * @param Response|null $response インポート元の Response オブジェクト
     */
    public function __construct(Response $response = null)
    {
        $this->headerList = [];
        $this->cookieList = [];
        if ($response !== null) {
            $this->importResponse($response);
        }
    }

    /**
     * 指定された Response オブジェクトの状態をこのビルダーにインポートします。
     * Content-Type と Content-Length は、再ビルド時に Body から再計算されるためインポートの対象から除外されます。
     *
     * @param Response $response インポートする Response オブジェクト
     */
    private function importResponse(Response $response): void
    {
        $body = $response->getBody();
        if ($body !== EmptyBody::getInstance()) {
            $this->body = $body;
        }

        $ignoreList = ["Content-Type", "Content-Length"];
        foreach ($response->getHeaderList() as $header) {
            if (!in_array($header->getName(), $ignoreList)) {
                $this->setHeader($header);
            }
        }

        $this->status     = $response->getStatus();
        $this->cookieList = $response->getCookieList();
    }

    /**
     * レスポンスボディを設定します。
     *
     * @param Body $body ボディオブジェクト
     * @return ResponseBuilder メソッドチェーンのための自身のインスタンス
     */
    public function setBody(Body $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * 設定されているレスポンスボディを取得します。
     *
     * @return Body ボディオブジェクト (未設定時は EmptyBody)
     */
    public function getBody(): Body
    {
        return $this->body ?? EmptyBody::getInstance();
    }

    /**
     * HTTP ステータスを設定します。
     *
     * @param Status $status ステータスオブジェクト
     * @return ResponseBuilder メソッドチェーンのための自身のインスタンス
     */
    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * 設定されている HTTP ステータスを取得します。
     *
     * @return Status ステータスオブジェクト (未設定時は 200 OK)
     */
    public function getStatus(): Status
    {
        return ($this->status === null) ? Status::getOK() : $this->status;
    }

    /**
     * HTTP ステータスが明示的に設定されているかどうかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasStatus(): bool
    {
        return ($this->status !== null);
    }

    /**
     * ヘッダーフィールドを設定します。EmptyField の場合は無視されます。
     * 既存の同名ヘッダーがある場合は上書きされます。
     *
     * @param HeaderField $header 設定するヘッダーフィールド
     * @return ResponseBuilder メソッドチェーンのための自身のインスタンス
     */
    public function setHeader(HeaderField $header): self
    {
        if ($header === EmptyField::getInstance()) {
            return $this;
        }

        $name = strtolower($header->getName());

        $this->headerList[$name] = $header;
        return $this;
    }

    /**
     * 設定されているすべてのヘッダーを取得します。
     *
     * @return HeaderField[] ヘッダー名をキー (小文字) とした連想配列
     */
    public function getHeaderList(): array
    {
        return $this->headerList;
    }

    /**
     * 単一の Cookie を設定します。
     *
     * @param string $name Cookie 名
     * @param string $value Cookie の値
     * @param CookieAttributes|null $attr Cookie に付与する属性 (省略時はデフォルトの属性が適用されます)
     * @return ResponseBuilder メソッドチェーンのための自身のインスタンス
     */
    public function setCookie(string $name, string $value, CookieAttributes $attr = null): self
    {
        $this->cookieList[$name] = new Cookie($name, $value, $attr);
        return $this;
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

    /**
     * このオブジェクトの設定内容に基づいて Response インスタンスを生成します。
     *
     * @return Response 構築されたレスポンスオブジェクト
     */
    public function build(): Response
    {
        return Response::newInstance($this);
    }
}
