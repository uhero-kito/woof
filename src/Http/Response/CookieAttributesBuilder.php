<?php

namespace Woof\Http\Response;

use InvalidArgumentException;

/**
 * CookieAttributes オブジェクトを構築するためのビルダークラスです。
 */
class CookieAttributesBuilder
{
    /**
     * 設定するドメイン名です。
     *
     * @var string
     */
    private $domain;

    /**
     * 設定するサーバー上のパスです。
     *
     * @var string
     */
    private $path;

    /**
     * 設定する有効期限 (Unix time) です。
     *
     * @var int
     */
    private $expires;

    /**
     * 設定する Secure フラグです。
     *
     * @var boolean
     */
    private $secure;

    /**
     * 設定する HttpOnly フラグです。
     *
     * @var bool
     */
    private $httpOnly;

    /**
     * 設定する SameSite 属性の値 ("Strict", "Lax", "None" などの文字列) です。
     *
     * @var string
     */
    private $sameSite;

    /**
     * 有効とするドメイン名を設定します。
     *
     * @param string $domain ドメイン名
     * @return CookieAttributesBuilder このオブジェクト自身
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * 設定されているドメイン名を取得します。
     *
     * @return string ドメイン名 (未設定時は空文字列)
     */
    public function getDomain(): string
    {
        return $this->domain ?? "";
    }

    /**
     * 有効とするパスを設定します。
     *
     * @param string $path パス文字列
     * @return CookieAttributesBuilder このオブジェクト自身
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 設定されているパスを取得します。
     *
     * @return string パス文字列 (未設定時は空文字列)
     */
    public function getPath(): string
    {
        return $this->path ?? "";
    }

    /**
     * 有効期限を Unix time で設定します。
     *
     * @param int $expires 有効期限の Unix time
     * @return CookieAttributesBuilder このオブジェクト自身
     */
    public function setExpires(int $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * 設定されている有効期限 (Unix time) を取得します。
     *
     * @return int 有効期限の Unix time (未設定時は 0)
     */
    public function getExpires(): int
    {
        return $this->expires ?? 0;
    }

    /**
     * Secure フラグ (HTTPS 接続でのみ送信するかどうか) を設定します。
     *
     * @param bool $secure 有効にする場合は true
     * @return CookieAttributesBuilder このオブジェクト自身
     */
    public function setSecure(bool $secure): self
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * 設定されている Secure フラグの状態を取得します。
     *
     * @return bool Secure フラグが有効な場合は true (未設定時は false)
     */
    public function isSecure(): bool
    {
        return $this->secure ?? false;
    }

    /**
     * HttpOnly フラグ (JavaScript からのアクセスを禁止) を設定します。
     *
     * @param bool $httpOnly 有効にする場合は true
     * @return CookieAttributesBuilder このオブジェクト自身
     */
    public function setHttpOnly(bool $httpOnly): self
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    /**
     * 設定されている HttpOnly フラグの状態を取得します。
     *
     * @return bool HttpOnly フラグが有効な場合は true (未設定時は false)
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly ?? false;
    }

    /**
     * SameSite 属性を設定します。
     * 引数に指定可能な文字列は "Strict", "Lax", "None", 空文字列のいずれかとなります。
     * 空文字列を指定した場合 SameSite 属性は付与されなくなります。
     *
     * @param string $value 適用する SameSite 属性の値
     * @return CookieAttributesBuilder このオブジェクト自身
     * @throws InvalidArgumentException 許可された値以外の文字列が指定された場合
     */
    public function setSameSite(string $value): self
    {
        $validList = ["Strict", "Lax", "None", ""];
        $subject   = ucfirst(strtolower($value));
        if (!in_array($subject, $validList)) {
            throw new InvalidArgumentException("Invalid SameSite value: '{$value}'");
        }
        $this->sameSite = $subject;
        return $this;
    }

    /**
     * SameSite 属性の値を返します。
     * セットされていない場合は空文字列を返します。
     *
     * @return string SameSite 属性の値 ("Strict", "Lax", "None", 空文字列のいずれか)
     */
    public function getSameSite(): string
    {
        return $this->sameSite ?? "";
    }

    /**
     * このビルダーの設定内容に基づいて CookieAttributes インスタンスを構築します。
     *
     * @return CookieAttributes 構築された CookieAttributes オブジェクト
     */
    public function build(): CookieAttributes
    {
        return CookieAttributes::newInstance($this);
    }
}
