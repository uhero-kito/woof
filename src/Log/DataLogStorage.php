<?php

namespace Woof\Log;

use InvalidArgumentException;
use Woof\DataStorage;

/**
 * DataStorage を利用してログを保存する LogStorage の実装です。
 *
 * 物理的なファイルの読み書きを DataStorage インタフェースに委譲することで、
 * ローカルディスクだけでなく、クラウドストレージやメモリなど、様々なストレージ媒体へのログ出力を可能にします。
 */
class DataLogStorage implements LogStorage
{
    /**
     * データの永続化を担う DataStorage インスタンスです。
     *
     * @var DataStorage
     */
    private $dataStorage;

    /**
     * ログファイル名 (保存キー) の先頭に付与される文字列です。
     *
     * @var string
     */
    private $prefix;

    /**
     * ログファイル名 (保存キー) の末尾に付与される文字列 (拡張子など) です。
     *
     * @var string
     */
    private $suffix;

    /**
     * ログの保存先となる DataStorage を指定して DataLogStorage オブジェクトを生成します。
     *
     * @param DataStorage $data ログの保存先となる DataStorage インスタンス
     * @param string $prefix ログファイル名の先頭に付与される文字列。デフォルトは "app"
     * @param string $suffix ログファイル名の末尾に付与される文字列。デフォルトは ".log"
     * @throws InvalidArgumentException 第 2 引数の prefix に空文字列が指定された場合
     */
    public function __construct(DataStorage $data, string $prefix = "app", string $suffix = ".log")
    {
        if (!strlen($prefix)) {
            throw new InvalidArgumentException("Prefix is required");
        }
        $this->dataStorage = $data;
        $this->prefix      = $prefix;
        $this->suffix      = $suffix;
    }

    /**
     * 指定された内容でログを DataStorage に追記します。
     *
     * @param string $content 出力するログの内容
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return bool 書き込みに成功した場合に true
     */
    public function write(string $content, int $time, int $level): bool
    {
        return $this->dataStorage->append($this->formatKey($time), $content . PHP_EOL);
    }

    /**
     * 発生時刻から DataStorage に保存するためのキー (ファイル名) を生成して返します。
     *
     * @param int $time ログの発生時刻 (Unix time)
     * @return string 生成された保存キー
     */
    private function formatKey(int $time): string
    {
        $datePart = date("Ymd", $time);
        return "{$this->prefix}-{$datePart}{$this->suffix}";
    }
}
