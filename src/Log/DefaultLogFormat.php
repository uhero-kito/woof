<?php

namespace Woof\Log;

/**
 * 標準的な形式でアプリケーションログを書式化する LogFormat の実装です。
 */
class DefaultLogFormat implements LogFormat
{
    /**
     * date() の引数として使用されるフォーマットです。
     *
     * @var string
     */
    private $dateFormat;

    /**
     * 指定された日付フォーマットでオブジェクトを生成します。
     *
     * @param string $dateFormat date() 関数で使用可能なフォーマット文字列。省略時は "Y-m-d H:i:s" が適用されます
     */
    public function __construct(string $dateFormat = "")
    {
        $this->dateFormat = strlen($dateFormat) ? $dateFormat : "Y-m-d H:i:s";
    }

    /**
     * 指定された情報を指定の書式でフォーマットした文字列を返します。
     *
     * @param string $message 出力するログメッセージ
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return string フォーマットされたログ文字列
     */
    public function format(string $message, int $time, int $level): string
    {
        $label = $this->formatLogLevel($level);
        $date  = date($this->dateFormat, $time);
        return "[{$date}][{$label}] {$message}";
    }

    /**
     * 指定された定数に応じたラベル ("ERROR", "INFO" など) を返します。
     *
     * @param int $level ログレベル定数
     * @return string 対応するログレベルのラベル文字列
     */
    private function formatLogLevel(int $level): string
    {
        static $labels = [
            Logger::LEVEL_ERROR => "ERROR",
            Logger::LEVEL_ALERT => "ALERT",
            Logger::LEVEL_INFO  => "INFO ",
            Logger::LEVEL_DEBUG => "DEBUG",
        ];
        return $labels[$level];
    }
}
