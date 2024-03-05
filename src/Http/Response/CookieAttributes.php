<?php

namespace Woof\Http\Response;

/**
 * Cookie に付与される各種属性 (有効期限・ドメイン・パス・Secure フラグなど) を保持するデータクラスです。
 */
class CookieAttributes
{
    /**
     * Cookie が有効なドメインです。
     *
     * @var string
     */
    private $domain;

    /**
     * Cookie が有効なパスです。
     *
     * @var string
     */
    private $path;

    /**
     * Cookie の有効期限 (Unix time) です。
     *
     * @var int
     */
    private $expires;

    /**
     * HTTPS 接続でのみ Cookie を送信するかどうかを制御する Secure フラグです。
     *
     * @var boolean
     */
    private $secure;

    /**
     * JavaScript からのアクセスを禁止するかどうかを制御する HttpOnly フラグです。
     *
     * @var bool
     */
    private $httpOnly;

    /**
     * クロスサイトリクエスト時の Cookie 送信を制御する SameSite 属性の値です。
     * "Strict", "Lax", "None", 空文字列のいずれかを保持します。
     *
     * @var string
     */
    private $sameSite;

    /**
     * このクラスは CookieAttributesBuilder を使用して構築するため、直接インスタンス化することはできません。
     */
    private function __construct()
    {

    }

    /**
     * CookieAttributesBuilder の状態を元に、新しい CookieAttributes インスタンスを生成します。
     *
     * このメソッドは CookieAttributesBuilder::build() から参照されます。
     *
     * @param CookieAttributesBuilder $builder 構築済みのビルダーオブジェクト
     * @return CookieAttributes 生成された CookieAttributes オブジェクト
     * @ignore
     */
    public static function newInstance(CookieAttributesBuilder $builder): self
    {
        $instance           = new self();
        $instance->domain   = $builder->getDomain();
        $instance->path     = $builder->getPath();
        $instance->expires  = $builder->getExpires();
        $instance->secure   = $builder->isSecure();
        $instance->httpOnly = $builder->isHttpOnly();
        $instance->sameSite = $builder->getSameSite();
        return $instance;
    }

    /**
     * 設定されているドメイン名を取得します。
     *
     * @return string ドメイン名
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * 設定されているパスを取得します。
     *
     * @return string パス
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 設定されている有効期限 (Unix time) を取得します。
     *
     * @return int 有効期限の Unix time
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * Secure フラグが有効かどうかを確認します。
     *
     * @return bool 有効な場合は true
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * HttpOnly フラグが有効かどうかを確認します。
     *
     * @return bool 有効な場合は true
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * SameSite 属性の値を取得します。
     *
     * @return string SameSite 属性の値 ("Strict", "Lax", "None", 空文字列のいずれか)
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }
}
