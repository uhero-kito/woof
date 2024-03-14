<?php

namespace Woof\Log;

use Woof\System\Clock;
use Woof\System\DefaultClock;

/**
 * アプリケーションのログ出力を担うクラスです。
 *
 * このクラスは直接インスタンス化することはできません。
 * LoggerBuilder クラスの build() メソッドを使用してください。
 */
class Logger
{
    /**
     * エラーレベルのログ出力を示す定数です。
     *
     * @var int
     */
    const LEVEL_ERROR = 0;

    /**
     * アラートレベルのログ出力を示す定数です。
     *
     * @var int
     */
    const LEVEL_ALERT = 1;

    /**
     * インフォレベルのログ出力を示す定数です。
     *
     * @var int
     */
    const LEVEL_INFO  = 2;

    /**
     * デバッグレベルのログ出力を示す定数です。
     *
     * @var int
     */
    const LEVEL_DEBUG = 3;

    /**
     * 出力対象とする閾値となるログレベルです。
     *
     * @see Logger::LEVEL_ERROR
     * @see Logger::LEVEL_ALERT
     * @see Logger::LEVEL_INFO
     * @see Logger::LEVEL_DEBUG
     * @var int
     */
    private $logLevel;

    /**
     * 複数行のメッセージを1つのログとして処理するかどうかのフラグです。
     *
     * @var bool
     */
    private $multiple;

    /**
     * ログメッセージを書式化するフォーマッタです。
     *
     * @var LogFormat
     */
    private $format;

    /**
     * ログの物理的な書き込みを担うストレージです。
     *
     * @var LogStorage
     */
    private $storage;

    /**
     * ログの発生時刻を提供するクロックです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 外部からのインスタンス生成を禁止することで getInstance() の使用を強制します。
     */
    private function __construct()
    {
    }

    /**
     * このメソッドは LoggerBuilder::build() から参照されます。
     *
     * @param LoggerBuilder $builder 値が設定された LoggerBuilder のインスタンス
     * @return Logger 構築された Logger インスタンス
     * @ignore
     */
    public static function newInstance(LoggerBuilder $builder): self
    {
        $instance           = new self();
        $instance->logLevel = $builder->getLogLevel();
        $instance->multiple = $builder->getMultiple();
        $instance->format   = $builder->getFormat();
        $instance->storage  = $builder->getStorage();
        $instance->clock    = $builder->getClock();
        return $instance;
    }

    /**
     * ログの書き込みを一切行わない Logger インスタンスを返します。
     *
     * @return Logger ログ出力を行わない Logger インスタンス
     */
    public static function getNopLogger(): self
    {
        // @codeCoverageIgnoreStart
        static $instance = null;
        if ($instance === null) {
            $instance           = new self();
            $instance->logLevel = -1;
            $instance->multiple = false;
            $instance->format   = new DefaultLogFormat();
            $instance->storage  = NullLogStorage::getInstance();
            $instance->clock    = DefaultClock::getInstance();
        }
        // @codeCoverageIgnoreEnd
        return $instance;
    }

    /**
     * この Logger に設定されているログレベルを取得します。
     *
     * @return int 設定されているログレベル定数
     */
    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    /**
     * 複数行のログを一度に処理するかどうかを取得します。
     *
     * @return bool 複数行の文字列を一度に処理する場合は true、行単位でログに追記する場合は false
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * この Logger に設定されている LogFormat オブジェクトを取得します。
     *
     * @return LogFormat ログの書式化を行う LogFormat オブジェクト
     */
    public function getFormat(): LogFormat
    {
        return $this->format;
    }

    /**
     * この Logger に設定されている LogStorage オブジェクトを取得します。
     *
     * @return LogStorage ログの保存先となる LogStorage オブジェクト
     */
    public function getStorage(): LogStorage
    {
        return $this->storage;
    }

    /**
     * この Logger に設定されている Clock オブジェクトを取得します。
     *
     * @return Clock ログの発生時刻を提供する Clock オブジェクト
     */
    public function getClock(): Clock
    {
        return $this->clock;
    }

    /**
     * 指定された内容をレベル ERROR で記録します。
     *
     * @param mixed $value 記録する内容 (文字列、配列、オブジェクトなど)
     * @return bool 記録に成功した場合に true
     */
    public function error($value): bool
    {
        return $this->log($value, self::LEVEL_ERROR);
    }

    /**
     * 指定された内容をレベル ALERT で記録します。
     * この Logger に設定されているログレベルが ERROR の場合は無視されます。
     *
     * @param mixed $value 記録する内容 (文字列、配列、オブジェクトなど)
     * @return bool 記録に成功した場合に true
     */
    public function alert($value): bool
    {
        return $this->log($value, self::LEVEL_ALERT);
    }

    /**
     * 指定された内容をレベル INFO で記録します。
     * この Logger に設定されているログレベルが ERROR, ALERT の場合は無視されます。
     *
     * @param mixed $value 記録する内容 (文字列、配列、オブジェクトなど)
     * @return bool 記録に成功した場合に true
     */
    public function info($value): bool
    {
        return $this->log($value, self::LEVEL_INFO);
    }

    /**
     * 指定された内容をレベル DEBUG で記録します。
     * この Logger に設定されているログレベルが DEBUG 以外の場合は無視されます。
     *
     * @param mixed $value 記録する内容 (文字列、配列、オブジェクトなど)
     * @return bool 記録に成功した場合に true
     */
    public function debug($value): bool
    {
        return $this->log($value, self::LEVEL_DEBUG);
    }

    /**
     * @param mixed $value 記録する内容
     * @param int $level ログレベル定数
     * @return bool 記録に成功した場合に true
     */
    private function log($value, int $level): bool
    {
        if ($this->logLevel < $level) {
            return true;
        }
        if (!is_string($value)) {
            return $this->log($this->getStringValue($value), $level);
        }
        $time   = $this->clock->getTime();
        $lines  = $this->multiple ? [$value] : preg_split("/\\r\\n|\\r|\\n/", $value);
        $result = true;
        foreach ($lines as $line) {
            $content = $this->format->format($line, $time, $level);
            $result  = $this->storage->write($content, $time, $level) && $result;
        }
        return $result;
    }

    /**
     * @param mixed $value 文字列に変換する対象の値
     * @return string 変換された文字列
     */
    private function getStringValue($value): string
    {
        if (is_object($value) && method_exists($value, "__toString")) {
            return $value->__toString();
        }
        if (is_object($value) || is_array($value)) {
            return trim(print_r($value, true));
        }
        if ($value === null) {
            return "(NULL)";
        }
        if ($value === true) {
            return "(TRUE)";
        }
        if ($value === false) {
            return "(FALSE)";
        }
        return (string) $value;
    }
}
