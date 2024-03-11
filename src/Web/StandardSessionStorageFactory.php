<?php

namespace Woof\Web;

use Woof\Config;
use Woof\DataStorage;
use Woof\FileDataStorage;
use Woof\Log\Logger;
use Woof\Web\Session\FileSessionContainer;
use Woof\Web\Session\SessionStorage;
use Woof\Web\Session\SessionStorageBuilder;

/**
 * 設定情報 (Config) を元に、標準的な SessionStorage インスタンスを構築するファクトリクラスです。
 *
 * このクラスは WebEnvironment の構築時に自動的に参照されます。
 * ただし WebEnvironmentBuilder にて SessionStorage が明示的に設定されている場合はそちらが優先され、このクラスは使用されません。
 *
 * インスタンスの構築時には、引数の Config オブジェクトから "session" セクションが読み込まれます。
 * "session" セクションでは以下のプロパティを指定できます。
 *
 * - dirname: セッションファイルの保存先ディレクトリ名 (デフォルト: "sessions") です。引数の DataStorage のベースディレクトリを基準とした相対パスとなります。
 * - keyname: セッションのキーとして使用される Cookie 名 (デフォルト: PHP の session_name() の値) です。
 * - max-age: セッションの有効期間 (秒数) です。60 から 7200 の間で指定します (デフォルト: PHP の session.gc_maxlifetime の値) 。
 * - gc-probability: ガベージコレクションの実行確率です。0.0 から 1.0 の間で指定します (デフォルト: PHP の gc_probability と gc_divisor から算出した値) 。
 */
class StandardSessionStorageFactory
{
    /**
     * 与えられた設定と DataStorage を元に、SessionStorage インスタンスを生成します。
     * セッションの保存先ディレクトリが存在しない場合は自動的に作成します。
     *
     * @param Config $config アプリケーション全体の設定をあらわす Config オブジェクト
     * @param DataStorage|null $data ファイルシステムのルートなどを管理するストレージオブジェクト
     * @param Logger|null $logger エラー出力用の Logger
     * @return SessionStorage 構築された SessionStorage オブジェクト
     */
    public function create(Config $config, DataStorage $data = null, Logger $logger = null): SessionStorage
    {
        $sub      = $config->getSubConfig("session");
        $savePath = $this->getSessionSavePath($sub, $data);
        is_dir($savePath) || mkdir($savePath, 0777, true);

        return (new SessionStorageBuilder())
            ->setSessionContainer(new FileSessionContainer($savePath, $logger))
            ->setKey($this->getSessionKey($sub))
            ->setMaxAge($this->getMaxAge($sub))
            ->setGcProbability($this->getGcProbability($sub))
            ->build();
    }

    /**
     * 設定情報からセッションの保存先ディレクトリパスを決定します。
     * DataStorage が FileDataStorage の場合はその管理下のディレクトリを返し、
     * それ以外の場合は PHP の設定 (session_save_path) またはシステムのテンポラリディレクトリを返します。
     *
     * @param Config $sub "session" セクションの設定をあらわす Config オブジェクト
     * @param DataStorage|null $data ストレージオブジェクト
     * @return string 保存先ディレクトリの絶対パス
     */
    private function getSessionSavePath(Config $sub, DataStorage $data = null)
    {
        if ($data instanceof FileDataStorage) {
            $dirname = $sub->getString("dirname", "sessions");
            return $data->formatPath($dirname);
        } else {
            $savePath = session_save_path();
            return strlen($savePath) ? $savePath : sys_get_temp_dir();
        }
    }

    /**
     * 設定情報からセッションキー (Cookie 名) を決定します。
     * 設定がない場合は PHP のデフォルト (session_name()) の値が使用されます。
     *
     * @param Config $sub "session" セクションの設定をあらわす Config オブジェクト
     * @return string セッションキー
     */
    private function getSessionKey(Config $sub): string
    {
        $def  = session_name();
        $name = $sub->getString("keyname", $def);
        return strlen($name) ? $name : $def;
    }

    /**
     * 設定情報からセッションの有効期間 (秒数) を決定します。
     * 60 秒から 7200 秒の間で制限され、設定がない場合は PHP のデフォルト (session.gc_maxlifetime) が使用されます。
     *
     * @param Config $sub "session" セクションの設定をあらわす Config オブジェクト
     * @return int 有効期間の秒数
     */
    private function getMaxAge(Config $sub): int
    {
        return $sub->getInt("max-age", ini_get("session.gc_maxlifetime"), 60, 7200);
    }

    /**
     * 設定情報からガベージコレクションの実行確率を決定します。
     * 0.0 から 1.0 の間で制限され、設定がない場合は PHP のデフォルト (session.gc_probability, session.gc_divisor) が計算されて使用されます。
     *
     * @param Config $sub "session" セクションの設定をあらわす Config オブジェクト
     * @return float ガベージコレクションの実行確率
     */
    private function getGcProbability(Config $sub): float
    {
        $p   = ini_get("session.gc_probability");
        $d   = ini_get("session.gc_divisor");
        $def = (0 < $p && 0 < $d) ? (float) ($p / $d) : 0.0;
        return $sub->getFloat("gc-probability", $def, 0.0, 1.0);
    }
}
