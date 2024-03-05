<?php

namespace Woof\Http\Response;

/**
 * HTTP レスポンスで送信する Cookie (クッキー) の情報を保持するクラスです。
 */
class Cookie
{
    /**
     * Cookie の各種属性 (有効期限・ドメイン・パス・Secure フラグなど) を保持するオブジェクトです。
     *
     * @var CookieAttributes
     */
    private $attr;

    /**
     * この Cookie の名前です。
     *
     * @var string
     */
    private $name;

    /**
     * この Cookie の値です。
     *
     * @var string
     */
    private $value;

    /**
     * Cookie の名前・値・属性 (任意) を指定して Cookie インスタンスを生成します。
     *
     * @param string $name Cookie の名前
     * @param string $value Cookie の値
     * @param CookieAttributes|null $attr Cookie の各種属性 (省略時はデフォルトの空属性が適用されます)
     */
    public function __construct(string $name, string $value, CookieAttributes $attr = null)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->attr  = $attr ?? self::getEmptyAttributes();
    }

    /**
     * Cookie の名前を取得します。
     *
     * @return string Cookie の名前
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Cookie の値を取得します。
     *
     * @return string Cookie の値
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Cookie が有効なドメインを取得します。
     *
     * @return string ドメイン名
     */
    public function getDomain(): string
    {
        return $this->attr->getDomain();
    }

    /**
     * Cookie が有効なパスを取得します。
     *
     * @return string パス
     */
    public function getPath(): string
    {
        return $this->attr->getPath();
    }

    /**
     * Cookie の有効期限を Unix time で取得します。
     *
     * @return int 有効期限の Unix time (セッション Cookie の場合は 0)
     */
    public function getExpires(): int
    {
        return $this->attr->getExpires();
    }

    /**
     * HTTPS 接続でのみ送信されるかどうかのフラグ (Secure 属性) を取得します。
     *
     * @return bool セキュア Cookie の場合は true
     */
    public function isSecure(): bool
    {
        return $this->attr->isSecure();
    }

    /**
     * JavaScript からのアクセスが禁止されているかどうかのフラグ (HttpOnly 属性) を取得します。
     *
     * @return bool HttpOnly が有効な場合は true
     */
    public function isHttpOnly(): bool
    {
        return $this->attr->isHttpOnly();
    }

    /**
     * クロスサイトリクエスト時の Cookie 送信を制御する SameSite 属性の値を取得します。
     *
     * @return string SameSite 属性の値 ("Strict", "Lax", "None", 空文字列のいずれか)
     */
    public function getSameSite(): string
    {
        return $this->attr->getSameSite();
    }

    /**
     * デフォルト値で構成された空の CookieAttributes オブジェクトを返します。
     *
     * @return CookieAttributes 空の属性オブジェクト
     * @codeCoverageIgnore
     */
    private static function getEmptyAttributes(): CookieAttributes
    {
        static $attr = null;
        if ($attr === null) {
            $attr = (new CookieAttributesBuilder())->build();
        }
        return $attr;
    }
}
