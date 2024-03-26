<?php

namespace Woof\Web\Session;

use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\FileHandler;

/**
 * ファイルシステムを使用してセッションデータを管理する SessionContainer の実装です。
 *
 * 指定されたディレクトリ配下に対して、セッションデータの読み書きや、有効期限切れに伴う破棄などの一連の管理を行います。
 */
class FileSessionContainer implements SessionContainer
{
    /**
     * セッションデータの保存先ディレクトリを操作する FileHandler です。
     *
     * @var FileHandler
     */
    private $handler;

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
     * セッション処理の共通ロジックを提供するヘルパーです。
     *
     * @var SessionContainerHelper
     */
    private $helper;

    /**
     * セッションを保存するディレクトリ名を指定して FileSessionContainer インスタンスを生成します。
     *
     * @param string $dirname セッションファイルを保存するディレクトリ
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param Clock|null $clock 時刻計算用のクロック (未指定時は DefaultClock インスタンス)
     */
    public function __construct(string $dirname, Logger $logger = null, Clock $clock = null)
    {
        $this->handler = new FileHandler($dirname);
        $this->logger  = $logger ?? Logger::getNopLogger();
        $this->clock   = $clock ?? DefaultClock::getInstance();
        $this->helper  = new SessionContainerHelper();
    }

    /**
     * 保存先ディレクトリを走査し、最終更新日時から計算して有効期限が切れているセッションファイルを削除します。
     *
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return int 削除されたセッションファイルの件数
     */
    public function cleanExpiredSessions(int $maxAge): int
    {
        $files = $this->handler->getFiles();
        $now   = $this->clock->getTime();
        $count = 0;

        foreach ($this->helper->filterSessionKeys($files) as $file) {
            $limit = $this->handler->getModifiedTime($file) + $maxAge;
            if (0 < $limit && $limit < $now) {
                $this->handler->remove($file) && $count++;
                $this->logger->debug("Session removed: '{$file}'");
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
        $file = "sess_{$id}";
        if (!$this->handler->contains($file)) {
            return false;
        }
        return $this->clock->getTime() < ($this->handler->getModifiedTime($file) + $maxAge);
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
        $file = "sess_{$id}";
        if (!$this->handler->contains($file)) {
            return [];
        }
        try {
            $this->handler->setModifiedTime($file, $this->clock->getTime());
            $serialized = trim($this->handler->get($file));
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
     * @return bool 書き込みに成功した場合に true
     */
    public function save(string $id, array $data): bool
    {
        $file       = "sess_{$id}";
        $serialized = $this->helper->serialize($data);
        $result     = $this->handler->put($file, $serialized);

        if (!$result) {
            $this->logger->alert("Failed to save session to '{$file}'");
        }

        return $result;
    }
}
