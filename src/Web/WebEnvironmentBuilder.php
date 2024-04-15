<?php

namespace Woof\Web;

use Woof\EnvironmentBuilder;
use Woof\Http\HeaderParser;
use Woof\Web\Cache\VariantStorage;
use Woof\Web\Session\SessionStorage;

/**
 * WebEnvironment オブジェクトを構築するためのビルダークラスです。
 */
class WebEnvironmentBuilder extends EnvironmentBuilder
{
    /**
     * 設定する SessionStorage オブジェクトです。
     *
     * @var SessionStorage
     */
    private $sessionStorage;

    /**
     * 設定する VariantStorage オブジェクトです。
     *
     * @var VariantStorage
     */
    private $variantStorage;

    /**
     * 設定する HeaderParser オブジェクトです。
     *
     * @var HeaderParser
     */
    private $headerParser;

    /**
     * セッションの管理・永続化を行う SessionStorage を設定します。
     *
     * 通常は設定ファイル (Config) の内容をもとに標準の SessionStorage が自動で構築されるため、
     * 基本的にこのメソッドを呼び出す必要はありません。
     * Redis やデータベースなどを利用した独自のセッション管理機構を組み込みたい場合や、
     * テスト時にモックオブジェクトを注入したい場合などに使用します。
     *
     * @param SessionStorage $sessionStorage カスタムの SessionStorage
     * @return WebEnvironmentBuilder このオブジェクト自身
     */
    public function setSessionStorage(SessionStorage $sessionStorage): self
    {
        $this->sessionStorage = $sessionStorage;
        return $this;
    }

    /**
     * SessionStorage が明示的に設定されているかを判定します。
     *
     * @return bool 設定されている場合に true
     */
    public function hasSessionStorage(): bool
    {
        return ($this->sessionStorage !== null);
    }

    /**
     * 明示的に設定された SessionStorage を取得します。
     *
     * @return SessionStorage 設定された SessionStorage オブジェクト
     */
    public function getSessionStorage(): SessionStorage
    {
        return $this->sessionStorage;
    }

    /**
     * キャッシュされた View の出力結果を管理する VariantStorage を設定します。
     *
     * 通常は設定ファイル (Config) の内容をもとに標準の VariantStorage が自動で構築されるため、
     * 基本的にこのメソッドを呼び出す必要はありません。
     * Redis やデータベースなどを利用した独自のキャッシュ管理機構を組み込みたい場合や、
     * テスト時にモックオブジェクトを注入したい場合などに使用します。
     *
     * @param VariantStorage $variantStorage カスタムの VariantStorage
     * @return WebEnvironmentBuilder このオブジェクト自身
     */
    public function setVariantStorage(VariantStorage $variantStorage): self
    {
        $this->variantStorage = $variantStorage;
        return $this;
    }

    /**
     * VariantStorage が明示的に設定されているかを判定します。
     *
     * @return bool 設定されている場合に true
     */
    public function hasVariantStorage(): bool
    {
        return ($this->variantStorage !== null);
    }

    /**
     * 明示的に設定された VariantStorage を取得します。
     *
     * @return VariantStorage
     */
    public function getVariantStorage(): VariantStorage
    {
        return $this->variantStorage;
    }

    /**
     * HTTP ヘッダー文字列を解析するための HeaderParser を設定します。
     *
     * 通常はフレームワーク標準のパーサーが使用されるため、未指定で構いません。
     * アプリケーション独自の特殊なヘッダー形式に対応させたい場合や、
     * 標準のパース処理の挙動を上書きしたい場合などに使用します。
     *
     * @param HeaderParser $parser カスタムの HeaderParser
     * @return WebEnvironmentBuilder このオブジェクト自身
     */
    public function setHeaderParser(HeaderParser $parser): self
    {
        $this->headerParser = $parser;
        return $this;
    }

    /**
     * HeaderParser が明示的に設定されているかを判定します。
     *
     * @return bool 設定されている場合に true
     */
    public function hasHeaderParser(): bool
    {
        return ($this->headerParser !== null);
    }

    /**
     * 明示的に設定されたヘッダーパーサーを取得します。
     *
     * @return HeaderParser 設定された HeaderParser オブジェクト
     */
    public function getHeaderParser(): HeaderParser
    {
        return $this->headerParser;
    }

    /**
     * このオブジェクトの設定内容に基づいて WebEnvironment インスタンスを構築します。
     *
     * @return WebEnvironment 構築された WebEnvironment オブジェクト
     */
    public function build(): WebEnvironment
    {
        return WebEnvironment::newInstance($this);
    }
}
