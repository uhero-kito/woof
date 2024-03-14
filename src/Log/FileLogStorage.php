<?php

namespace Woof\Log;

use InvalidArgumentException;
use Woof\System\FileSystemException;

/**
 * ファイルシステム上の所定のファイルにログを追記する LogStorage の実装です。
 *
 * 実際のアプリケーション稼働時において、サーバー内のローカルディスクにログを永続化する用途で使用されます。
 */
class FileLogStorage implements LogStorage
{
    /**
     * ログファイルが保管されるディレクトリです。
     *
     * @var string
     */
    private $dirname;

    /**
     * ログファイルの先頭に付与される文字列です。
     * ファイル名は {prefix}-YYYYMMDD.log となります。
     *
     * @var string
     */
    private $prefix;

    /**
     * 指定されたディレクトリをログの保存場所とする FileLogStorage オブジェクトを生成します。
     * 第 2 引数にログファイルの先頭の文字列を指定することができます。
     * 第 2 引数に使用できる文字は半角英数および ".", "_", "-" です。
     *
     * @param string $dirname ログの保存先ディレクトリ
     * @param string $prefix ログファイルの先頭の文字列。デフォルトは "app"
     * @throws FileSystemException 第 1 引数に指定されたディレクトリが見つからない場合
     * @throws InvalidArgumentException 第 2 引数に指定された文字列が不正な場合
     */
    public function __construct(string $dirname, string $prefix = "app")
    {
        if (!is_dir($dirname)) {
            throw new FileSystemException("Directory not found: {$dirname}");
        }
        if (!preg_match("/\\A[a-zA-Z0-9_\\.\\-]+\\z/", $prefix)) {
            throw new InvalidArgumentException("Invalid prefix: '{$prefix}'");
        }
        $this->dirname = $dirname;
        $this->prefix  = $prefix;
    }

    /**
     * 指定された内容でログファイルに追記します。
     *
     * @param string $content 出力するログの内容
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return bool 書き込みに成功した場合に true
     */
    public function write(string $content, int $time, int $level): bool
    {
        $filename = $this->formatFilename($time);
        $fullpath = "{$this->dirname}/{$filename}";
        return file_put_contents($fullpath, $content . PHP_EOL, FILE_APPEND);
    }

    /**
     * 発生時刻からログファイル名を生成して返します。
     *
     * @param int $time ログの発生時刻 (Unix time)
     * @return string 生成されたファイル名
     */
    private function formatFilename(int $time): string
    {
        $datePart = date("Ymd", $time);
        return "{$this->prefix}-{$datePart}.log";
    }
}
