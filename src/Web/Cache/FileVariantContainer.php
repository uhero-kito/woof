<?php

namespace Woof\Web\Cache;

use LogicException;
use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\FileHandler;

/**
 * ファイルシステムを使用してバリアントの保存と読み込みを行う VariantContainer の実装です。
 *
 * 指定されたディレクトリ内に `{$id}.dat` という形式でコンテンツを永続化します。
 */
class FileVariantContainer implements VariantContainer
{
    /**
     * バリアントファイルの保存先ディレクトリを操作する FileHandler です。
     *
     * @var FileHandler
     */
    private $handler;

    /**
     * ログ出力に使用する Logger オブジェクトです。
     *
     * @var Logger
     */
    private $logger;

    /**
     * 時刻参照に使用する Clock オブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 保存先ディレクトリと必要なオブジェクトを指定してオブジェクトを初期化します。
     *
     * @param string $dirname キャッシュファイルの保存先ディレクトリ
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param Clock|null $clock 時刻計算用のクロック (未指定時は DefaultClock インスタンス)
     */
    public function __construct(string $dirname, Logger $logger = null, Clock $clock = null)
    {
        $this->handler = new FileHandler($dirname);
        $this->logger  = $logger ?? Logger::getNopLogger();
        $this->clock   = $clock ?? DefaultClock::getInstance();
    }

    /**
     * 有効期限を過ぎた古いバリアントデータをストレージから一括で削除します。
     * 削除された各ファイルについて、DEBUG レベルでログを出力します。
     *
     * @param int $maxAge キャッシュの有効期限 (秒)
     * @return int 削除されたレコード (ファイル) の数
     */
    public function cleanExpiredVariants(int $maxAge): int
    {
        $files = $this->handler->getFiles();
        $now   = $this->clock->getTime();
        $count = 0;

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== "dat") {
                continue;
            }

            $limit = $this->handler->getModifiedTime($file) + $maxAge;
            if (0 < $limit && $limit < $now) {
                $this->handler->remove($file) && $count++;
                $fullpath = $this->handler->formatFullpath($file);
                $this->logger->debug("Expired variant cache file deleted: '{$fullpath}'");
            }
        }
        return $count;
    }

    /**
     * 指定された ID のバリアントのファイル名を構築して返します。
     *
     * @param string $id バリアント ID
     * @return string ファイル名
     */
    private function formatFilename(string $id): string
    {
        return "{$id}.dat";
    }

    /**
     * 指定された ID のバリアントがこのコンテナ内に有効な状態で存在するかどうかを判定します。
     *
     * @param string $id     バリアントの ID
     * @param int    $maxAge キャッシュの有効期限 (秒)
     * @return bool 有効なバリアントが存在する場合は true
     */
    public function contains(string $id, int $maxAge): bool
    {
        $filename = $this->formatFilename($id);
        if (!$this->handler->contains($filename)) {
            return false;
        }
        return $this->clock->getTime() < ($this->handler->getModifiedTime($filename) + $maxAge);
    }

    /**
     * 指定された ID のバリアントをこのコンテナから読み込みます。
     *
     * @param string $id 読み込むバリアントの ID
     * @return Variant 読み込まれたバリアントオブジェクト
     * @throws LogicException バリアントファイルが存在しないか、または読み込みに失敗した場合
     */
    public function load(string $id): Variant
    {
        $filename = $this->formatFilename($id);
        if (!$this->handler->contains($filename)) {
            throw new LogicException("Variant file not found for ID: '{$id}'");
        }

        $content = $this->handler->get($filename);
        $mtime   = $this->handler->getModifiedTime($filename);

        // @codeCoverageIgnoreStart
        if ($mtime === 0) {
            throw new LogicException("Failed to read variant file for ID: '{$id}'");
        }
        // @codeCoverageIgnoreEnd

        return new Variant($id, $content, $mtime);
    }

    /**
     * 指定されたコンテンツをバリアントとしてこのコンテナに保存します。
     *
     * @param string $id      保存するバリアントの ID
     * @param string $content 保存するコンテンツ内容
     * @return bool 保存に成功した場合は true
     */
    public function save(string $id, string $content): bool
    {
        $filename = $this->formatFilename($id);
        $result   = $this->handler->put($filename, $content);

        // @codeCoverageIgnoreStart
        if (!$result) {
            $this->logger->alert("Failed to save variant file for ID: '{$id}'");
        }
        // @codeCoverageIgnoreEnd

        return $result;
    }
}
