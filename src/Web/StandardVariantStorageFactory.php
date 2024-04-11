<?php

namespace Woof\Web;

use Woof\Config;
use Woof\DataStorage;
use Woof\Log\Logger;
use Woof\Web\Cache\DataVariantContainer;
use Woof\Web\Cache\NullVariantContainer;
use Woof\Web\Cache\VariantStorage;
use Woof\Web\Cache\VariantStorageBuilder;

/**
 * 設定情報 (Config) を元に、標準的な VariantStorage インスタンスを構築するファクトリクラスです。
 *
 * このクラスは WebEnvironment の構築時に自動的に参照されます。
 * ただし WebEnvironmentBuilder にて VariantStorage が明示的に設定されている場合はそちらが優先され、このクラスは使用されません。
 *
 * インスタンスの構築時には、引数の Config オブジェクトから "cache" セクションが読み込まれます。
 * "cache" セクションでは以下のプロパティを指定できます。
 *
 * - dirname: キャッシュファイルの保存先ディレクトリ名 (デフォルト: "cache") です。引数の DataStorage のベースディレクトリを基準とした相対パスとなります。
 * - suffix: キャッシュファイルの末尾に付与される文字列 (デフォルト: ".dat") です。
 * - max-age: キャッシュの有効時間 (秒数) です。 (デフォルト: 3600)
 * - gc-probability: ガベージコレクションの実行確率です。0.0 から 1.0 の間で指定します。 (デフォルト: 0.01)
 *
 * 引数の DataStorage は基本的に FileDataStorage を想定しており、上記プロパティのドキュメント (「キャッシュファイルの保存先ディレクトリ名」など) もそれを前提とした記載としています。
 * しかし FileDataStorage 以外の任意の DataStorage 実装も適用可能です。
 * 独自の DataStorage を導入する場合は「ディレクトリ名」を「イニシャル・セグメント」等に適宜読み替えてください。
 */
class StandardVariantStorageFactory
{
    /**
     * 与えられた設定と DataStorage を元に VariantStorage インスタンスを生成します。
     *
     * DataStorage が未指定 (null) の場合は、キャッシュを行わないダミーのインスタンスを返します。
     *
     * @param Config $config アプリケーション全体の設定をあらわす Config オブジェクト
     * @param DataStorage|null $data キャッシュデータの読み書きを行うストレージオブジェクト
     * @param Logger|null $logger エラー出力用の Logger
     * @return VariantStorage 構築された VariantStorage オブジェクト
     */
    public function create(Config $config, DataStorage $data = null, Logger $logger = null): VariantStorage
    {
        if ($data === null) {
            return (new VariantStorageBuilder())
                ->setVariantContainer(NullVariantContainer::getInstance())
                ->setGcProbability(0.0)
                ->build();
        }

        $sub       = $config->getSubConfig("cache");
        $prefix    = $sub->getString("dirname", "cache");
        $suffix    = $sub->getString("suffix", ".dat");
        $container = new DataVariantContainer($data, $prefix, $suffix, $logger);

        return (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge($this->getMaxAge($sub))
            ->setGcProbability($this->getGcProbability($sub))
            ->build();
    }

    /**
     * 設定情報からキャッシュの有効期間 (秒数) を決定します。
     * 設定がない場合はデフォルトの 3600 秒が使用されます。
     *
     * @param Config $sub "cache" セクションの設定をあらわす Config オブジェクト
     * @return int 有効期間の秒数
     */
    private function getMaxAge(Config $sub): int
    {
        return $sub->getInt("max-age", 3600);
    }

    /**
     * 設定情報からガベージコレクションの実行確率を決定します。
     * 0.0 から 1.0 の間で制限され、設定がない場合はデフォルトの 0.01 (1%) が使用されます。
     *
     * @param Config $sub "cache" セクションの設定をあらわす Config オブジェクト
     * @return float ガベージコレクションの実行確率
     */
    private function getGcProbability(Config $sub): float
    {
        return $sub->getFloat("gc-probability", 0.01, 0.0, 1.0);
    }
}
