<?php

namespace Woof\Web\Cache;

use LogicException;
use Woof\DataStorage;
use Woof\Log\Logger;
use Woof\System\Clock;
use Woof\System\DefaultClock;

/**
 * DataStorage を使用してバリアントの保存と読み込みを行う VariantContainer の実装です。
 *
 * 指定されたイニシャル・セグメント配下に指定のサフィックスを持つ形式でコンテンツを永続化します。
 */
class DataVariantContainer implements VariantContainer
{
    /**
     * バリアントデータを保存する DataStorage です。
     *
     * @var DataStorage
     */
    private $storage;

    /**
     * バリアントデータのキーの先頭に付与されるイニシャル・セグメントです。
     *
     * @var string
     */
    private $prefix;

    /**
     * キー名の末尾に付与される文字列 (拡張子など) です。
     *
     * @var string
     */
    private $suffix;

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
     * DataStorage とイニシャル・セグメントなどを指定してオブジェクトを初期化します。
     *
     * @param DataStorage $storage バリアントデータを保存する DataStorage
     * @param string $prefix バリアントデータの読み書きを行う対象となるイニシャル・セグメント (例: "cache/views")
     * @param string $suffix キーの末尾に付与される文字列 (デフォルトは空文字列)
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param Clock|null $clock 時刻計算用のクロック (未指定時は DefaultClock インスタンス)
     */
    public function __construct(DataStorage $storage, string $prefix, string $suffix = "", Logger $logger = null, Clock $clock = null)
    {
        $this->storage = $storage;
        $this->prefix  = trim($prefix, "/");
        $this->suffix  = $suffix;
        $this->logger  = $logger ?? Logger::getNopLogger();
        $this->clock   = $clock ?? DefaultClock::getInstance();
    }

    /**
     * 有効期限を過ぎた古いバリアントデータをストレージから一括で削除します。
     * 削除された各データについて、DEBUG レベルでログを出力します。
     *
     * @param int $maxAge キャッシュの有効期限 (秒)
     * @return int 削除されたレコードの数
     */
    public function cleanExpiredVariants(int $maxAge): int
    {
        $keys   = $this->storage->getKeys($this->prefix);
        $now    = $this->clock->getTime();
        $count  = 0;
        $suffix = $this->suffix;

        foreach ($keys as $key) {
            if ($suffix !== "" && substr($key, -strlen($suffix)) !== $suffix) {
                continue;
            }

            $limit = $this->storage->getModifiedTime($key) + $maxAge;
            if (0 < $limit && $limit < $now) {
                $this->storage->remove($key) && $count++;
                $this->logger->debug("Expired variant cache data deleted: '{$key}'");
            }
        }

        return $count;
    }

    /**
     * バリアント ID を元に、DataStorage 上のキーを生成します。
     *
     * @param string $id バリアント ID
     * @return string バリアントキー
     */
    private function formatKey(string $id): string
    {
        $filename = $id . $this->suffix;
        return (strlen($this->prefix) > 0) ? "{$this->prefix}/{$filename}" : $filename;
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
        $key = $this->formatKey($id);
        if (!$this->storage->contains($key)) {
            return false;
        }

        return $this->clock->getTime() < ($this->storage->getModifiedTime($key) + $maxAge);
    }

    /**
     * 指定された ID のバリアントをこのコンテナから読み込みます。
     *
     * @param string $id 読み込むバリアントの ID
     * @return Variant 読み込まれたバリアントオブジェクト
     * @throws LogicException バリアントデータが存在しないか、または読み込みに失敗した場合
     */
    public function load(string $id): Variant
    {
        $key = $this->formatKey($id);
        if (!$this->storage->contains($key)) {
            throw new LogicException("Variant data not found for ID: '{$id}'");
        }

        $content = $this->storage->get($key);
        $mtime   = $this->storage->getModifiedTime($key);

        // @codeCoverageIgnoreStart
        if ($mtime === 0) {
            throw new LogicException("Failed to read variant data for ID: '{$id}'");
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
        $key    = $this->formatKey($id);
        $result = $this->storage->put($key, $content);

        // @codeCoverageIgnoreStart
        if (!$result) {
            $this->logger->alert("Failed to save variant data for ID: '{$id}'");
        }
        // @codeCoverageIgnoreEnd

        return $result;
    }
}
