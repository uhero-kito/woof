<?php

namespace Woof\Web;

use LogicException;
use Woof\Config;
use Woof\Environment;
use Woof\Http\HeaderParser;
use Woof\Http\Request;
use Woof\Http\RequestLoader;
use Woof\Log\Logger;
use Woof\System\Variables;
use Woof\Web\Cache\VariantStorage;
use Woof\Web\Session\SessionStorage;

/**
 * Web アプリケーションの実行環境を表現するクラスです。
 * 既定の Environment の内容に加えて HTTP リクエスト, SessionStorage, VariantStorage, Context (URL の書式化をサポートするオブジェクト)
 * などの Web アプリケーションに特化した機能を提供します。
 */
class WebEnvironment extends Environment
{
    /**
     * セッションの管理・永続化を行うオブジェクトです。
     *
     * @var SessionStorage
     */
    private $sessionStorage;

    /**
     * キャッシュされた View の内容を管理するオブジェクトです。
     *
     * @var VariantStorage
     */
    private $variantStorage;

    /**
     * URL の書式化などに使用するオブジェクトです。
     *
     * @var Context
     */
    private $context;

    /**
     * クライアントから送信された HTTP リクエストの情報を保持するオブジェクトです。
     *
     * @var Request
     */
    private $clientRequest;

    /**
     * このクラスは WebEnvironmentBuilder を使用して初期化します。
     * 外部からの直接的なインスタンス化はできません。
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * WebEnvironmentBuilder の状態を元に、新しい WebEnvironment インスタンスを生成します。
     *
     * @param WebEnvironmentBuilder $builder 構築済みのビルダーオブジェクト
     * @return WebEnvironment 生成された WebEnvironment オブジェクト
     * @throws LogicException 必須パラメータが設定されていない場合
     */
    public static function newInstance(WebEnvironmentBuilder $builder): self
    {
        $instance = new self();
        $instance->init($builder);

        $config = $instance->getConfig();
        $data   = $instance->hasDataStorage() ? $instance->getDataStorage() : null;
        $logger = $instance->getLogger();
        $parser = $builder->hasHeaderParser() ? $builder->getHeaderParser() : null;
        $sess   = $builder->hasSessionStorage() ? $builder->getSessionStorage() : (new StandardSessionStorageFactory())->create($config, $data, $logger);
        $vars   = $builder->hasVariantStorage() ? $builder->getVariantStorage() : (new StandardVariantStorageFactory())->create($config, $data, $logger);

        $instance->sessionStorage = $sess;
        $instance->variantStorage = $vars;
        $instance->context        = self::createContext($config);
        $instance->clientRequest  = self::createClientRequest($instance->getVariables(), $logger, $parser);
        return $instance;
    }

    /**
     * 設定情報から Context オブジェクトを構築します。
     *
     * @param Config $config 設定オブジェクト
     * @return Context 構築された Context オブジェクト
     */
    private static function createContext(Config $config): Context
    {
        $rootPath  = $config->getString("app.root-path");
        $separator = $config->getString("app.arg-separator");
        return new Context($rootPath, $separator);
    }

    /**
     * サーバー変数などを元に、クライアントの送信した HTTP リクエストをあらわす Request オブジェクトを構築します。
     *
     * @param Variables $var PHP のスーパーグローバル変数を抽象化したオブジェクト
     * @param Logger $logger Request HTTP リクエストの解析時に発生したエラーを記録するための Logger オブジェクト
     * @param HeaderParser|null $parser ヘッダー解析用の HeaderParser
     * @return Request 構築された Request オブジェクト
     */
    private static function createClientRequest(Variables $var, Logger $logger, HeaderParser $parser = null)
    {
        return (new RequestLoader($logger, $parser))->load($var);
    }

    /**
     * 設定された SessionStorage オブジェクトを取得します。
     *
     * @return SessionStorage 設定された SessionStorage オブジェクト
     */
    public function getSessionStorage(): SessionStorage
    {
        return $this->sessionStorage;
    }

    /**
     * 設定された VariantStorage オブジェクトを取得します。
     *
     * @return VariantStorage
     */
    public function getVariantStorage(): VariantStorage
    {
        return $this->variantStorage;
    }

    /**
     * URL の書式化を行うための Context オブジェクトを取得します。
     *
     * @return Context URL の書式化などを行う Context オブジェクト
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * クライアントが送信した HTTP リクエストをあらわす Request オブジェクトを取得します。
     *
     * @return Request クライアントが送信した HTTP リクエストをあらわす Request オブジェクト
     */
    public function getClientRequest(): Request
    {
        return $this->clientRequest;
    }
}
