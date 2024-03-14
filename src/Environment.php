<?php

namespace Woof;

use InvalidArgumentException;
use LogicException;
use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\Random;
use Woof\System\Variables;

/**
 * アプリケーションの実行に必要な各種コンポーネント (設定・各種リソース・ログ・システム時刻など) を集約し、提供する基底クラスです。
 *
 * このクラスを通じて、アプリケーション内のどこからでも統一された方法で必要な機能やデータにアクセスすることができます。
 * 実行される環境 (CLI や WEB など) に応じて、本クラスを継承した以下の具象クラスを使い分けてください。
 *
 * - バッチ処理などの CLI 環境: `Woof\DefaultEnvironment` (生成には `DefaultEnvironmentBuilder` を使用)
 * - WEB アプリケーション環境: `Woof\Web\WebEnvironment` (生成には `WebEnvironmentBuilder` を使用)
 *
 * 各具象クラスは直接インスタンス化することはできず、対応する EnvironmentBuilder を通じて生成および初期化を行う必要があります。
 */
abstract class Environment
{
    /**
     * アプリケーションの設定値を提供するオブジェクトです。
     *
     * @var Config
     */
    private $config;

    /**
     * 各種リソースを提供するオブジェクトです。
     *
     * @var Resources
     */
    private $resources;

    /**
     * 動的なデータの読み書きを行うオブジェクトです。
     *
     * @var DataStorage
     */
    private $dataStorage;

    /**
     * アプリケーションのログ出力を担うオブジェクトです。
     *
     * @var Logger
     */
    private $logger;

    /**
     * アプリケーション内の基準時刻を提供するオブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 乱数を生成するオブジェクトです。
     *
     * @var Random
     */
    private $random;

    /**
     * 各種環境変数やスーパーグローバル変数にあたるデータを提供するオブジェクトです。
     *
     * @var Variables
     */
    private $variables;

    /**
     * ビルダーから渡された設定をもとに、このオブジェクトを初期化します。
     *
     * @param EnvironmentBuilder $builder 各種コンポーネントがセットされたビルダー
     * @throws LogicException Config (設定オブジェクト) が指定されていない場合
     */
    protected function init(EnvironmentBuilder $builder): void
    {
        if (!$builder->hasConfig()) {
            throw new LogicException("Config is not specified");
        }
        $config    = $builder->getConfig();
        $resources = $builder->getResources();
        $data      = $builder->hasDataStorage() ? $builder->getDataStorage() : null;
        $logger    = $builder->hasLogger() ? $builder->getLogger() : (new StandardLoggerFactory())->create($config, $data);

        $this->config      = $config;
        $this->resources   = $resources;
        $this->dataStorage = $data;
        $this->logger      = $logger;
        $this->clock       = $builder->getClock();
        $this->random      = $builder->getRandom();
        $this->variables   = $builder->getVariables();
    }

    /**
     * アプリケーションの設定値を提供する Config オブジェクトを取得します。
     *
     * @return Config 設定オブジェクト
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * テンプレートや翻訳ファイルなどのリソースを提供する Resources オブジェクトを取得します。
     *
     * @return Resources リソースオブジェクト
     */
    public function getResources(): Resources
    {
        return $this->resources;
    }

    /**
     * 動的なデータの読み書きを行う DataStorage オブジェクトが設定されているか調べます。
     *
     * @return bool DataStorage が設定されている場合に true
     */
    public function hasDataStorage(): bool
    {
        return ($this->dataStorage !== null);
    }

    /**
     * 動的なデータの読み書きを行う DataStorage オブジェクトを取得します。
     *
     * @return DataStorage DataStorage オブジェクト
     * @throws LogicException DataStorage が設定されていない状態で呼び出された場合
     */
    public function getDataStorage(): DataStorage
    {
        if ($this->dataStorage === null) {
            throw new LogicException("DataStorage is not set");
        }
        return $this->dataStorage;
    }

    /**
     * アプリケーションのログ出力を担う Logger オブジェクトを取得します。
     *
     * @return Logger Logger オブジェクト
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * アプリケーション内の基準時刻を提供する Clock オブジェクトを取得します。
     *
     * @return Clock オブジェクト
     */
    public function getClock()
    {
        return $this->clock;
    }

    /**
     * 現在の基準時刻を Unix time (整数) で取得します。
     * 内部的には getClock()->getTime() と同じ結果を返します。
     *
     * @return int 現在の基準時刻 (Unix time)
     */
    public function now()
    {
        return $this->clock->getTime();
    }

    /**
     * 乱数を生成する Random オブジェクトを取得します。
     *
     * @return Random Random オブジェクト
     */
    public function getRandom()
    {
        return $this->random;
    }

    /**
     * 乱数値を整数で取得します。
     * 最小値と最大値を指定することで、指定された範囲内の乱数を取得することができます。
     *
     * @param int|null $min 返される乱数値の最小値 (デフォルトは 0)
     * @param int|null $max 返される乱数値の最大値 (デフォルトはシステムの最大乱数値)
     * @return int 生成された乱数値
     * @throws InvalidArgumentException $max に $min よりも小さい値が指定された場合
     */
    public function rand(int $min = null, int $max = null): int
    {
        // @codeCoverageIgnoreStart
        static $randMax = null;
        if ($randMax === null) {
            $randMax = mt_getrandmax();
        }
        // @codeCoverageIgnoreEnd

        $random = $this->getRandom();
        if ($min === null || $max === null) {
            return $random->next();
        }

        if ($max < $min) {
            throw new InvalidArgumentException("max({$max}) is smaller than min({$min})");
        }
        $rand = (int) ($random->next() / $randMax * (1 + $max - $min));
        return min($min + $rand, $max);
    }

    /**
     * サーバーやリクエストの環境変数を提供する Variables オブジェクトを取得します。
     *
     * @return Variables Variables オブジェクト
     */
    public function getVariables(): Variables
    {
        return $this->variables;
    }
}
