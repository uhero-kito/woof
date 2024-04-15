<?php

namespace Woof\Web;

use InvalidArgumentException;
use Woof\Http\ContentDisposition;
use Woof\Http\HeaderField;
use Woof\Http\HttpDate;
use Woof\Http\QualityValues;
use Woof\Http\Request;
use Woof\Http\Response;
use Woof\Http\Response\Body;
use Woof\Http\Response\CookieAttributes;
use Woof\Http\Response\CookieAttributesBuilder;
use Woof\Http\Response\EmptyBody;
use Woof\Http\Response\TextBody;
use Woof\Http\ResponseBuilder;
use Woof\Http\Status;
use Woof\Http\TextField;
use Woof\Locale;

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
     * View のキャッシュ機能を有効にするかどうかを示すフラグです。
     *
     * @var bool
     */
    private $enablesCache = false;

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
     * View のキャッシュ機能を有効化・無効化します。
     *
     * @param bool $enablesCache キャッシュを有効にする場合は true
     * @return Operator このオブジェクト自身
     */
    public function setEnablesCache(bool $enablesCache): self
    {
        $this->enablesCache = $enablesCache;
        return $this;
    }

    /**
     * View のキャッシュ機能が有効に設定されているかを判定します。
     *
     * @return bool 有効な場合に true
     */
    public function getEnablesCache(): bool
    {
        return $this->enablesCache;
    }

    /**
     * 現在のレスポンス構築状態において、キャッシュ処理を実行すべきかどうかを判定します。
     *
     * @return bool キャッシュ有効化フラグが true かつ、Body が ViewBody の場合に true
     */
    private function isCacheEnabled(): bool
    {
        return $this->enablesCache && ($this->builder->getBody() instanceof ViewBody);
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
     * If-Modified-Since と If-None-Match のいずれかが一致する場合に true となります。
     *
     * @param int $mtime リソースの最終更新日時 (Unix time)
     * @param string $etag リソースの ETag 値
     * @return bool 変更されていないと判定された場合に true
     */
    public function checkNotModified(int $mtime, string $etag): bool
    {
        $request = $this->request;
        $ifm     = $request->getHeader("If-Modified-Since")->getValue();
        $ifn     = $request->getHeader("If-None-Match")->getValue();

        // どちらも指定されていない場合は false (例えば新規アクセスの場合)
        if ($ifm === null && $ifn === null) {
            return false;
        }
        // If-Modified-Since が指定されており、一致しない場合は false
        if ($ifm !== null && $ifm !== $mtime) {
            return false;
        }
        // If-None-Match が指定されており、一致しない場合は false
        if ($ifn !== null && $ifn !== $etag) {
            return false;
        }

        return true;
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
     * エンドユーザー, WEB アプリケーション, OS (システム) の優先順位でロケールを解決し、
     * フォールバック探索用に連結された Locale オブジェクトを構築して返します。
     *
     * 解決可能なロケールが 1 つも存在しない場合は、ルートロケールを返します。
     *
     * @param Locale|null $locale 優先的に使用するエンドユーザーの Locale オブジェクト (未指定の場合は null)
     * @return Locale             解決および連結された Locale オブジェクト
     */
    public function getLocale(Locale $locale = null): Locale
    {
        return Locale::getRoot()
            ->append($this->resolveUserLocale($locale))
            ->append($this->resolveAppLocale())
            ->append($this->resolveSystemLocale());
    }

    /**
     * エンドユーザーのロケールを解決します。
     *
     * 引数に有効な Locale が指定された場合はそれを返し、未指定の場合は
     * Accept-Language ヘッダーから優先度が最も高い有効なロケールをパースして返します。
     *
     * @param Locale|null $locale 引数として指定された Locale オブジェクト
     * @return Locale             解決されたエンドユーザーの Locale (見つからなかった場合はルートロケール)
     */
    private function resolveUserLocale(Locale $locale = null): Locale
    {
        $root = Locale::getRoot();
        if ($locale !== null && $locale !== $root) {
            return $locale;
        }

        $acceptLang = $this->request->getHeader("Accept-Language");
        if (!($acceptLang instanceof QualityValues)) {
            return $root;
        }

        foreach (array_keys($acceptLang->getValue()) as $langStr) {
            $parsed = $this->parseLocaleSafely($langStr);
            if ($parsed !== $root) {
                return $parsed;
            }
        }
        return $root;
    }

    /**
     * WEB アプリケーションのロケールを解決します。
     *
     * Environment の Config (app.json など) に設定された "locale" 値を参照し、Locale としてパースします。
     *
     * @return Locale 解決されたアプリケーションの Locale (設定がないか、またはパース失敗時はルートロケール)
     */
    private function resolveAppLocale(): Locale
    {
        $appConfig    = $this->env->getConfig();
        $appLocaleStr = $appConfig->getString("app.locale", "");
        return $this->parseLocaleSafely($appLocaleStr);
    }

    /**
     * OS (システム) のロケールを解決します。
     *
     * intl 拡張機能が有効な場合は php.ini の `intl.default_locale` に設定されている値を Locale オブジェクトとしてパースします。
     * 無効な場合はルートロケールを返します。
     *
     * @return Locale 解決されたシステムの Locale (設定がないか、またはパースに失敗した場合はルートロケール)
     */
    private function resolveSystemLocale(): Locale
    {
        $root = Locale::getRoot();

        if (!extension_loaded("intl")) {
            return $root;
        }

        // @codeCoverageIgnoreStart
        $sysLocaleStr = ini_get("intl.default_locale");
        if (!is_string($sysLocaleStr) || $sysLocaleStr === "") {
            return $root;
        }

        return $this->parseLocaleSafely($sysLocaleStr);
        // @codeCoverageIgnoreEnd
    }

    /**
     * 文字列から Locale オブジェクトへのパースを安全に試行します。
     *
     * ルートロケールやパース時の InvalidArgumentException を吸収しますが、
     * デバッグ時の追跡を容易にするため、失敗時には Logger へ DEBUG レベルで記録を残します。
     *
     * @param string $localeStr パース対象のロケール文字列
     * @return Locale           パースに成功した Locale オブジェクト (失敗時はルートロケール)
     */
    private function parseLocaleSafely(string $localeStr): Locale
    {
        $root = Locale::getRoot();
        if ($localeStr === "") {
            return $root;
        }
        try {
            return Locale::parseLocale($localeStr);
        } catch (InvalidArgumentException $e) {
            // パースに失敗した場合はデバッグログを残し、ルートロケールを返します
            $this->env->getLogger()->debug("Failed to parse locale string '{$localeStr}': " . $e->getMessage());
            return $root;
        }
    }

    /**
     * これまでの操作内容に基づいて最終的な Response オブジェクトを構築します。
     * キャッシュ機能が有効な場合は、レンダリングをスキップしてキャッシュからの構築を試行します。
     *
     * @return Response 構築された Response オブジェクト
     */
    public function build(): Response
    {
        $builder = $this->builder;
        if (!$this->isCacheEnabled()) {
            return $builder->build();
        }

        $storage = $this->env->getVariantStorage();
        $body    = $builder->getBody();
        $variant = $storage->fetchVariant($body);

        $builder
            ->setHeader(new TextField("ETag", $variant->getId()))
            ->setHeader(new HttpDate("Last-Modified", $variant->getLastModified()));

        if ($this->checkNotModified($variant->getLastModified(), $variant->getId())) {
            return $builder
                ->setStatus(Status::get304())
                ->setBody(EmptyBody::getInstance())
                ->build();
        } else {
            return $builder
                ->setBody(new TextBody($variant->getContent(), $body->getContentType()))
                ->build();
        }
    }
}
