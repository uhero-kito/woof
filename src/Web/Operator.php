<?php

namespace Woof\Web;

use Woof\Http\ContentDisposition;
use Woof\Http\HeaderField;
use Woof\Http\Request;
use Woof\Http\Response;
use Woof\Http\Response\Body;
use Woof\Http\Response\CookieAttributes;
use Woof\Http\Response\CookieAttributesBuilder;
use Woof\Http\ResponseBuilder;
use Woof\Http\Status;
use Woof\Http\TextField;

/**
 * Web アプリケーション (Controller) 内で HTTP レスポンスを構築するまでの操作を統括するクラスです。
 */
class Operator
{
    /**
     * 現在処理中の HTTP リクエストをあらわすオブジェクトです。
     *
     * @var Request
     */
    private $request;

    /**
     * Web アプリケーションの実行環境をあらわすオブジェクトです。
     *
     * @var WebEnvironment
     */
    private $env;

    /**
     * HTTP レスポンスを構築するためのビルダーオブジェクトです。
     *
     * @var ResponseBuilder
     */
    private $builder;

    /**
     * 現在のセッションをあらわすオブジェクトです。遅延読み込みされます。
     *
     * @var Session
     */
    private $session;

    /**
     * 必要なオブジェクト群を指定して Operator インスタンスを初期化します。
     *
     * @param Request $request HTTP リクエスト
     * @param WebEnvironment $env この Web アプリケーションの実行環境をあらわす WebEnvironment
     * @param Response|null $response 既存の HTTP レスポンス (指定した場合はその状態を引き継ぎます)
     */
    public function __construct(Request $request, WebEnvironment $env, Response $response = null)
    {
        $this->request = $request;
        $this->env     = $env;
        $this->builder = new ResponseBuilder($response);
    }

    /**
     * 内部で保持している ResponseBuilder オブジェクトを返します。
     *
     * @return ResponseBuilder HTTP レスポンスの出力に使用する ResponseBuilder オブジェクト
     */
    public function getResponseBuilder(): ResponseBuilder
    {
        return $this->builder;
    }

    /**
     * 現在紐づいている HTTP リクエストに関連付けられたセッション情報を Session オブジェクトとして返します。
     *
     * 初回呼び出し時に SessionStorage から該当のセッションがロードされ、以降はキャッシュを返します。
     *
     * @return Session セッションオブジェクト
     */
    public function getSessionObject(): Session
    {
        if ($this->session === null) {
            $this->session = $this->env->getSessionStorage()->getSession($this->request);
        }
        return $this->session;
    }

    /**
     * 現在紐づいている HTTP リクエストに関連付けられたセッションにデータを設定します。
     *
     * 注意: PHP の既存のセッション機能 (スーパーグローバル変数の $_SESSION) とは異なり、
     * ここでセットした情報は saveSession() を明示的に実行しない限り永続化されません。
     *
     * @param string $key データキー
     * @param mixed $value 設定する値
     * @return Operator このオブジェクト自身
     */
    public function setSession(string $key, $value): self
    {
        $this->getSessionObject()->set($key, $value);
        return $this;
    }

    /**
     * 現在紐づいている HTTP リクエストに関連付けられたセッションからデータを取得します。
     * 存在しない場合は代替値を返します。
     *
     * @param string $key データキー
     * @param mixed $defaultValue 代替値 (デフォルトは null)
     * @return mixed 取得された値・代替値のいずれか
     */
    public function getSession(string $key, $defaultValue = null)
    {
        return $this->getSessionObject()->get($key, $defaultValue);
    }

    /**
     * 変更されたセッションデータをストレージに明示的に保存 (永続化) します。
     *
     * PHP の標準セッション ($ _SESSION) とは異なり、
     * Controller の処理中にエラーが発生した際に容易にロールバックできるようにするため、
     * このメソッドを実行しない限りセッションの変更は保存されません。
     *
     * 新規セッションの場合は、セッション ID をクライアントに保存するための Cookie を HTTP レスポンスにセットします。
     *
     * @return Operator このオブジェクト自身
     */
    public function saveSession(): self
    {
        $session = $this->getSessionObject();
        if ($session->isNew() && !$session->isChanged()) {
            return $this;
        }

        $ss = $this->env->getSessionStorage();
        $ss->save($session);
        if ($session->isNew()) {
            $attr = (new CookieAttributesBuilder())
                ->setPath($this->env->getContext()->getRootPath())
                ->build();
            $this->builder->setCookie($ss->getKey(), $session->getId(), $attr);
        }
        return $this;
    }

    /**
     * HTTP レスポンスのヘッダーを追加または上書きします。
     *
     * @param HeaderField $header 設定するヘッダーフィールド
     * @return Operator このオブジェクト自身
     */
    public function setHeader(HeaderField $header): self
    {
        $this->builder->setHeader($header);
        return $this;
    }

    /**
     * HTTP レスポンスの本文を出力するための View を指定します。
     *
     * 実行環境の Context や Resources と紐付けて、内部で ViewBody を構築します。
     *
     * @param View $view レンダリングを行う View オブジェクト
     * @return Operator このオブジェクト自身
     */
    public function setView(View $view): self
    {
        $this->builder->setBody(new ViewBody($view, $this->env->getResources(), $this->env->getContext()));
        return $this;
    }

    /**
     * HTTP レスポンスの本文 (Body オブジェクト) を直接指定します。
     *
     * @param Body $body 設定するボディオブジェクト
     * @return Operator このオブジェクト自身
     */
    public function setBody(Body $body): self
    {
        $this->builder->setBody($body);
        return $this;
    }

    /**
     * HTTP ステータスコードを指定します。
     *
     * @param Status $status HTTP ステータスコードをあらわす Status オブジェクト
     * @return Operator このオブジェクト自身
     */
    public function setStatus(Status $status): self
    {
        $this->builder->setStatus($status);
        return $this;
    }

    /**
     * クライアントに送信する Cookie を設定します。
     *
     * @param string $name Cookie 名
     * @param string $value Cookie の値
     * @param CookieAttributes|null $attr Cookie の属性
     * @return Operator このオブジェクト自身
     */
    public function setCookie(string $name, string $value, CookieAttributes $attr = null): self
    {
        $this->builder->setCookie($name, $value, $attr);
        return $this;
    }

    /**
     * パスとクエリパラメータから絶対 URL を構築して返します。
     *
     * @param string $path URL のパス部分
     * @param array $queryList 付与するクエリパラメータの連想配列
     * @return string 構築された絶対 URL
     */
    public function formatAbsoluteUrl(string $path, array $queryList): string
    {
        $href = $this->env->getContext()->formatHref($path, $queryList);
        if (preg_match("/\\Ahttps?:\\/\\//", $href)) {
            return $href;
        }

        $request = $this->request;
        $scheme  = $request->getScheme();
        if (preg_match("/\\A\\/\\//", $href)) {
            return "{$scheme}:{$href}";
        }

        $host = $request->getHost();
        return "{$scheme}://{$host}{$href}";
    }

    /**
     * 指定されたパスへのリダイレクト (Location ヘッダーと 302 ステータス) を設定します。
     * すでに HTTP ステータスが設定されている場合はステータスを上書きしません。
     *
     * @param string $appPath リダイレクト先のパス
     * @param array $queryList 付与するクエリパラメータの連想配列
     * @return Operator このオブジェクト自身
     */
    public function setRedirect(string $appPath, array $queryList = []): self
    {
        $url     = $this->formatAbsoluteUrl($appPath, $queryList);
        $builder = $this->builder;
        if (!$builder->hasStatus()) {
            $builder->setStatus(Status::get302());
        }
        $builder->setHeader(new TextField("Location", $url));
        return $this;
    }

    /**
     * クライアントのリクエストヘッダーを検査し、リソースが更新されていないかを判定します。
     * If-Modified-Since と If-None-Match の両方が一致する場合に true となります。
     *
     * @param int $mtime リソースの最終更新日時 (Unix time)
     * @param string $etag リソースの ETag 値
     * @return bool 変更されていないと判定された場合に true
     */
    public function checkNotModified(int $mtime, string $etag): bool
    {
        $request = $this->request;
        $ifm     = $request->getHeader("If-Modified-Since");
        $ifn     = $request->getHeader("If-None-Match");
        return ($ifm->getValue() === $mtime && $ifn->getValue() === $etag);
    }

    /**
     * HTTP レスポンスの本文データをブラウザに「名前を付けて保存」させるためのファイル名を指定します。
     * このメソッドが実行された場合 HTTP レスポンスに Content-Disposition ヘッダーが付与され、
     * 引数に指定されたファイル名が filename として設定されます。
     *
     * 引数なしでこのメソッドを実行することで、デフォルトの保存ファイル名を未指定にすることができます。
     *
     * @param string $filename 添付ファイル名
     * @return Operator このオブジェクト自身
     */
    public function setAttachmentFilename(string $filename = ""): self
    {
        return $this->setHeader(new ContentDisposition($filename));
    }

    /**
     * これまでの操作内容に基づいて最終的な Response オブジェクトを構築します。
     *
     * @return Response 構築された Response オブジェクト
     */
    public function build(): Response
    {
        return $this->builder->build();
    }
}
