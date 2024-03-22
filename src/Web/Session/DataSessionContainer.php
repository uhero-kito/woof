<?php

namespace Woof\Web\Session;

use Woof\DataStorage;
use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;

/**
 * DataStorage を使用してセッションデータを管理する SessionContainer の実装です。
 *
 * 指定されたイニシャル・セグメント (ファイルシステムにおける特定のサブディレクトリ名の相対パスなど)
 * の配下に対して、セッションデータの読み書きや有効期限切れに伴う破棄などの一連の管理を行います。
 */
class DataSessionContainer implements SessionContainer
{
    /**
     * セッションデータを保存する DataStorage です。
     *
     * @var DataStorage
     */
    private $storage;

    /**
     * セッションデータのキーの先頭に付与されるイニシャル・セグメントです。
     *
     * @var string
     */
    private $prefix;

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
     * DataStorage とイニシャル・セグメントなどを指定して DataSessionContainer インスタンスを生成します。
     *
     * @param DataStorage $storage セッションデータを保存する DataStorage
     * @param string $prefix セッションデータの読み書きを行う対象となるイニシャル・セグメント (ファイルシステムにおける特定のサブディレクトリ名の相対パスなど。例: "sessions")
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param Clock|null $clock 時刻計算用のクロック (未指定時は DefaultClock インスタンス)
     */
    public function __construct(DataStorage $storage, string $prefix, Logger $logger = null, Clock $clock = null)
    {
        $this->storage = $storage;
        $this->prefix  = trim($prefix, "/");
        $this->logger  = $logger ?? Logger::getNopLogger();
        $this->clock   = $clock ?? DefaultClock::getInstance();
    }

    /**
     * DataStorage を走査し、最終更新日時から計算して有効期限が切れているセッションを削除します。
     *
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return int 削除されたセッションの件数
     */
    public function cleanExpiredSessions(int $maxAge): int
    {
        $keys  = $this->storage->getKeys($this->prefix);
        $now   = $this->clock->getTime();
        $count = 0;

        foreach ($keys as $key) {
            $basename = basename($key);
            if (substr($basename, 0, 5) !== "sess_") {
                continue;
            }

            $limit = $this->storage->getModifiedTime($key) + $maxAge;
            if ($limit < $now) {
                $this->storage->remove($key) && $count++;
                $this->logger->debug("Session removed: '{$key}'");
            }
        }

        return $count;
    }

    /**
     * 指定された ID のセッションが存在し、かつ有効期限内であるかを判定します。
     *
     * @param string $id セッション ID
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return bool 有効なセッションが存在する場合に true
     */
    public function contains(string $id, int $maxAge): bool
    {
        $key = $this->formatKey($id);
        if (!$this->storage->contains($key)) {
            return false;
        }

        return $this->clock->getTime() < ($this->storage->getModifiedTime($key) + $maxAge);
    }

    /**
     * セッション ID を元に、DataStorage 上のキーを生成します。
     *
     * @param string $id セッション ID
     * @return string セッションキー
     */
    private function formatKey(string $id): string
    {
        return (strlen($this->prefix) > 0) ? "{$this->prefix}/sess_{$id}" : "sess_{$id}";
    }

    /**
     * セッションデータを読み込み、連想配列に復元して返します。
     * 読み込みに成功した場合はデータの更新日時を現在時刻に更新します。
     * データが存在しない・パースに失敗したなどの場合は空の配列を返します。
     *
     * @param string $id セッション ID
     * @return array 復元されたセッションデータの連想配列
     */
    public function load(string $id): array
    {
        $key = $this->formatKey($id);
        if (!$this->storage->contains($key)) {
            return [];
        }
        try {
            $this->storage->setModifiedTime($key, $this->clock->getTime());
            $serialized = trim($this->storage->get($key));
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
     * セッションデータをシリアライズし、DataStorage に保存します。
     *
     * @param string $id セッション ID
     * @param array $data 保存するセッションデータの連想配列
     * @return bool 書き込みに成功した場合に true
     */
    public function save(string $id, array $data): bool
    {
        $key        = $this->formatKey($id);
        $serialized = $this->serialize($data);
        $result     = $this->storage->put($key, $serialized);

        if (!$result) {
            $this->logger->alert("Failed to save session to '{$key}'");
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
