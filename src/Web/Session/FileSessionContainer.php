<?php

namespace Woof\Web\Session;

use DirectoryIterator;
use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\FileSystemException;

/**
 * ファイルシステムを使用してセッションデータを管理する SessionContainer の実装です。
 */
class FileSessionContainer implements SessionContainer
{
    /**
     * セッションファイルを保存するディレクトリのパスです。
     *
     * @var string
     */
    private $dirname;

    /**
     * エラー発生時などに情報を出力するための Logger オブジェクトです。
     *
     * @var Logger
     */
    private $logger;

    /**
     * 有効期限の計算などに使用する Clock オブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 保存先ディレクトリなどを指定して FileSessionContainer インスタンスを生成します。
     *
     * @param string $dirname セッションファイルを保存するディレクトリ
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param Clock|null $clock 時刻計算用のクロック (未指定時は DefaultClock インスタンス)
     * @throws FileSystemException 指定されたディレクトリが存在しない場合
     */
    public function __construct(string $dirname, Logger $logger = null, Clock $clock = null)
    {
        if (!is_dir($dirname)) {
            throw new FileSystemException("Directory not found: {$dirname}");
        }

        $this->dirname = $dirname;
        $this->logger  = $logger ?? Logger::getNopLogger();
        $this->clock   = $clock ?? DefaultClock::getInstance();
    }

    /**
     * 保存先ディレクトリを走査し、最終更新日時から計算して有効期限が切れているセッションファイルを削除します。
     *
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return int 削除されたセッションファイルの件数
     */
    public function cleanExpiredSessions(int $maxAge): int
    {
        $dir   = new DirectoryIterator($this->dirname);
        $now   = $this->clock->getTime();
        $count = 0;
        foreach ($dir as $entry) {
            $filename = $entry->getFilename();
            if (substr($filename, 0, 5) !== "sess_") {
                continue;
            }

            $path  = "{$this->dirname}/{$filename}";
            $limit = filemtime($path) + $maxAge;
            if ($limit < $now) {
                @unlink($path) && $count++;
                $this->logger->debug("Session removed: '{$filename}'");
            }
        }
        return $count;
    }

    /**
     * 指定された ID のセッションファイルが存在し、かつ有効期限内であるかを判定します。
     *
     * @param string $id セッション ID
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return bool 有効なセッションが存在する場合に true
     */
    public function contains(string $id, int $maxAge): bool
    {
        $filename = $this->formatFilename($id);
        if (!is_file($filename)) {
            return false;
        }
        return $this->clock->getTime() < (filemtime($filename) + $maxAge);
    }

    /**
     * セッション ID を元に、ファイルシステム上の絶対パスを生成します。
     *
     * @param string $id セッション ID
     * @return string セッションファイルのパス
     */
    private function formatFilename(string $id): string
    {
        return "{$this->dirname}/sess_{$id}";
    }

    /**
     * セッションファイルからデータを読み込み、連想配列に復元して返します。
     * 読み込みに成功した場合はファイルの更新日時 (mtime) を現在時刻に更新します。
     * ファイルが存在しない・パースに失敗したなどの場合は空の配列を返します。
     *
     * @param string $id セッション ID
     * @return array 復元されたセッションデータの連想配列
     */
    public function load(string $id): array
    {
        $filename = $this->formatFilename($id);
        if (!is_file($filename)) {
            return [];
        }
        try {
            touch($filename, $this->clock->getTime());
            $serialized = trim(file_get_contents($filename));
            $parser     = new ParserContext($serialized);
            return $parser->parse();
        } catch (ParseException $e) {
            $logger = $this->logger;
            $logger->alert("Failed to parse session for ID '{$id}'");
            $logger->alert($e->getMessage());
            return [];
        }
    }

    /**
     * セッションデータをシリアライズし、ファイルに書き込んで保存します。
     *
     * @param string $id セッション ID
     * @param array $data 保存するセッションデータの連想配列
     * @return bool 書き込みおよび権限変更に成功した場合に true
     */
    public function save(string $id, array $data): bool
    {
        $filename   = $this->formatFilename($id);
        $serialized = $this->serialize($data);
        $result     = file_put_contents($filename, $serialized) && chmod($filename, 0666);
        if (!$result) {
            $this->logger->alert("Failed to save session to '{$filename}'");
        }
        return $result;
    }

    /**
     * 連想配列を独自のセッションフォーマット文字列にシリアライズします。
     *
     * @param array $data シリアライズ対象の連想配列
     * @return string シリアライズされた文字列
     */
    private function serialize(array $data): string
    {
        $result = "";
        foreach ($data as $key => $value) {
            $serialized = serialize($value);
            $result     .= "{$key}|{$serialized}";
        }
        return $result;
    }
}
