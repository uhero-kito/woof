<?php

namespace Woof;

use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\DefaultRandom;
use Woof\System\Random;
use Woof\System\Variables;
use Woof\Util\FileProperties;

/**
 * Environment オブジェクトを構築するための基底のビルダークラスです。
 *
 * アプリケーションの実行に必要なコンポーネント (Config, Resources, DataStorage など) を段階的にセットアップし、
 * 最終的に build() メソッドを通じて Environment オブジェクトを生成します。
 */
abstract class EnvironmentBuilder
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
     * Config オブジェクトを設定します。
     *
     * @param Config $config 設定オブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 指定されたディレクトリからファイルを読み込む Config オブジェクトを設定します。
     *
     * @param string $dirname 設定ファイルが配置されているディレクトリのパス
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setConfigDir(string $dirname): self
    {
        $this->config = new Config(new FileProperties($dirname));
        return $this;
    }

    /**
     * Config が設定されているかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasConfig(): bool
    {
        return ($this->config !== null);
    }

    /**
     * 設定されている Config オブジェクトを取得します。
     *
     * @return Config 設定オブジェクト
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Resources オブジェクトを設定します。
     *
     * @param Resources $resources リソースオブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setResources(Resources $resources): self
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * 指定されたディレクトリからファイルを読み込む Resources オブジェクトを設定します。
     *
     * @param string $dirname リソースファイルが配置されているディレクトリのパス
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setResourcesDir(string $dirname): self
    {
        $this->resources = new FileResources($dirname);
        return $this;
    }

    /**
     * Resources が設定されているかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasResources(): bool
    {
        return ($this->resources !== null);
    }

    /**
     * 設定されている Resources オブジェクトを取得します。
     * 未設定の場合は NullResources のインスタンスを返します。
     *
     * @return Resources リソースオブジェクト
     */
    public function getResources(): Resources
    {
        return $this->resources ?? NullResources::getInstance();
    }

    /**
     * DataStorage オブジェクトを設定します。
     *
     * @param DataStorage $dataStorage データストレージオブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setDataStorage(DataStorage $dataStorage): self
    {
        $this->dataStorage = $dataStorage;
        return $this;
    }

    /**
     * 指定されたディレクトリをベースとする DataStorage オブジェクトを設定します。
     *
     * @param string $dirname データストレージとして使用するディレクトリのパス
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setDataStorageDir(string $dirname): self
    {
        $this->dataStorage = new FileDataStorage($dirname);
        return $this;
    }

    /**
     * DataStorage が設定されているかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasDataStorage(): bool
    {
        return ($this->dataStorage !== null);
    }

    /**
     * 設定されている DataStorage オブジェクトを取得します。
     *
     * @return DataStorage データストレージオブジェクト
     */
    public function getDataStorage(): DataStorage
    {
        return $this->dataStorage;
    }

    /**
     * Logger オブジェクトを設定します。
     *
     * @param Logger $logger ロガーオブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Logger が設定されているかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasLogger(): bool
    {
        return ($this->logger !== null);
    }

    /**
     * 設定されている Logger オブジェクトを取得します。
     * 未設定の場合はログを出力しない NOP ロガーを返します。
     *
     * @return Logger ロガーオブジェクト
     */
    public function getLogger(): Logger
    {
        return $this->logger ?? Logger::getNopLogger();
    }

    /**
     * Clock オブジェクトを設定します。
     *
     * @param Clock $clock クロックオブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setClock(Clock $clock): self
    {
        $this->clock = $clock;
        return $this;
    }

    /**
     * 設定されている Clock オブジェクトを取得します。
     * 未設定の場合はシステムの現在時刻を返す DefaultClock を返します。
     *
     * @return Clock クロックオブジェクト
     */
    public function getClock(): Clock
    {
        return $this->clock ?? DefaultClock::getInstance();
    }

    /**
     * Random オブジェクトを設定します。
     *
     * @param Random $random 乱数生成オブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setRandom(Random $random): self
    {
        $this->random = $random;
        return $this;
    }

    /**
     * 設定されている Random オブジェクトを取得します。
     * 未設定の場合はシステムの乱数生成機構を使用する DefaultRandom を返します。
     *
     * @return Random 乱数生成オブジェクト
     */
    public function getRandom(): Random
    {
        return $this->random ?? DefaultRandom::getInstance();
    }

    /**
     * Variables オブジェクトを設定します。
     *
     * @param Variables $variables 環境変数オブジェクト
     * @return EnvironmentBuilder このオブジェクト自身
     */
    public function setVariables(Variables $variables): self
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Variables が設定されているかを調べます。
     *
     * @return bool 設定されている場合に true
     */
    public function hasVariables(): bool
    {
        return ($this->variables !== null);
    }

    /**
     * 設定されている Variables オブジェクトを取得します。
     * 未設定の場合はデフォルトの環境変数 ($_SERVER など) をラップしたインスタンスを返します。
     *
     * @return Variables 環境変数オブジェクト
     */
    public function getVariables(): Variables
    {
        return $this->variables ?? Variables::getDefaultInstance();
    }
}
