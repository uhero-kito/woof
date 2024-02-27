<?php

namespace Woof;

use Woof\Log\DataLogStorage;
use Woof\Log\DefaultLogFormat;
use Woof\Log\Logger;
use Woof\Log\LoggerBuilder;

/**
 * アプリケーションの設定 (Config) と DataStorage を元に Logger インスタンスを生成するファクトリクラスです。
 *
 * このクラスは Environment の構築時に自動的に参照されます。
 * ただし EnvironmentBuilder にて Logger が明示的に設定されている場合はそちらが優先され、このクラスは使用されません。
 *
 * インスタンスの構築時には、引数の Config オブジェクトから "logger" セクションが読み込まれます。
 * "logger" セクションが存在しない場合は、ログ出力を行わない (NOP) ロガーが生成されます。
 * "logger" セクションでは以下のプロパティを指定できます。
 *
 * - dirname: ログファイルの保存先ディレクトリ名 (デフォルト: "logs") です。引数の DataStorage のベースディレクトリを基準とした相対パスとなります。
 * - prefix: ログファイルの接頭辞 (デフォルト: "app") です。 `dirname` と合わせて `{dirname}/{prefix}-{YYYYMMDD}.log` という形式のパスでログファイルが生成されます。
 * - format: 日時のフォーマット文字列 (デフォルト: 空文字列) です。
 *     - `date()` 関数で使用可能なフォーマットを指定することができます。
 *     - 空文字列 (未指定) の場合はデフォルトの日時フォーマットが適用されます。
 * - loglevel: 出力するログレベルです。"error", "alert", "info", "debug" のいずれかを指定します (デフォルト: "error")。
 * - multiple: 複数行の (改行を含む) メッセージを出力する際の挙動を指定する真偽値 (デフォルト: false) です。
 *     - true の場合: 1 つのログエントリとして改行を含んだまま出力されます。
 *     - false の場合: 改行ごとにメッセージを分割してログを追記します。
 */
class StandardLoggerFactory
{
    /**
     * 設定内容に基づいて Logger オブジェクトを生成します。
     * DataStorage が指定されていないか、または Config 内に "logger" セクションが存在しない場合は、
     * ログ出力を行わない (NOP) Logger を返します。
     *
     * @param Config $config Logger に関する設定を含む Config オブジェクト
     * @param DataStorage|null $data ログの保存先となる DataStorage オブジェクト
     * @return Logger 生成された Logger オブジェクト
     */
    public function create(Config $config, DataStorage $data = null): Logger
    {
        if ($data === null || !$config->contains("logger")) {
            return Logger::getNopLogger();
        }
        $sub      = $config->getSubConfig("logger");
        $dirname  = $sub->getString("dirname", "logs");
        $prefix   = $sub->getString("prefix", "app");
        $format   = $sub->getString("format", "");
        $logLevel = $sub->getString("loglevel", "error");
        $multiple = $sub->getBool("multiple");
        return (new LoggerBuilder())
            ->setStorage(new DataLogStorage($data, "{$dirname}/{$prefix}"))
            ->setFormat(new DefaultLogFormat($format))
            ->setLogLevel($this->detectLogLevel($logLevel))
            ->setMultiple($multiple)
            ->build();
    }

    /**
     * 文字列で指定されたログレベルを、対応する定数に変換します。
     *
     * @param string $logLevel ログレベルの文字列 ("error", "info" など)
     * @return int 対応するログレベル定数 (無効な値の場合は LEVEL_ERROR)
     */
    private function detectLogLevel(string $logLevel): int
    {
        $validList = [
            "error" => Logger::LEVEL_ERROR,
            "alert" => Logger::LEVEL_ALERT,
            "info"  => Logger::LEVEL_INFO,
            "debug" => Logger::LEVEL_DEBUG,
        ];
        $key = strtolower($logLevel);
        return $validList[$key] ?? Logger::LEVEL_ERROR;
    }
}
