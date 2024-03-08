<?php

namespace Woof\Web\Session;

use InvalidArgumentException;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\DefaultRandom;
use Woof\System\Random;

/**
 * SessionStorage オブジェクトを構築するためのビルダークラスです。
 */
class SessionStorageBuilder
{
    /**
     * 設定する SessionContainer オブジェクトです。
     *
     * @var SessionContainer
     */
    private $container;

    /**
     * 設定するセッションキー (Cookie 名) です。
     *
     * @var string
     */
    private $key;

    /**
     * 設定するセッションの有効期間 (秒数) です。
     *
     * @var int
     */
    private $maxAge;

    /**
     * 設定するガベージコレクションの実行確率です。
     *
     * @var float
     */
    private $gcProbaility;

    /**
     * 設定するクロックオブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 設定する乱数生成器オブジェクトです。
     *
     * @var Random
     */
    private $random;

    /**
     * SessionContainer を設定します。
     *
     * @param SessionContainer $container セッションデータを読み書きするための SessionContainer オブジェクト
     * @return SessionStorageBuilder このオブジェクト自身
     */
    public function setSessionContainer(SessionContainer $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * SessionContainer が設定されているかを判定します。
     *
     * @return bool 設定されている場合に true
     */
    public function hasSessionContainer(): bool
    {
        return ($this->container !== null);
    }

    /**
     * 設定されている SessionContainer を取得します。
     *
     * @return SessionContainer コンテナオブジェクト
     */
    public function getSessionContainer(): SessionContainer
    {
        return $this->container;
    }

    /**
     * セッションキー (Cookie 名) を設定します。
     *
     * @param string $key 英数字・アンダースコア・ドット・ハイフンで構成されたキー文字列
     * @return SessionStorageBuilder このオブジェクト自身
     * @throws InvalidArgumentException 不正な形式のキーが指定された場合
     */
    public function setKey(string $key): self
    {
        if (!preg_match("/\\A[a-zA-Z0-9_\\.\\-]+\\z/", $key)) {
            throw new InvalidArgumentException("Invalid session key: '{$key}'");
        }
        $this->key = $key;
        return $this;
    }

    /**
     * 設定されているセッションキーを取得します。
     *
     * @return string セッションキー (未設定時は空文字列)
     */
    public function getKey(): string
    {
        return $this->key ?? "";
    }

    /**
     * セッションの有効期間 (秒数) を設定します。
     *
     * @param int $maxAge 0 より大きい秒数
     * @return SessionStorageBuilder このオブジェクト自身
     * @throws InvalidArgumentException 0 以下の値が指定された場合
     */
    public function setMaxAge(int $maxAge): self
    {
        if ($maxAge <= 0) {
            throw new InvalidArgumentException("Invalid max-age value: {$maxAge}");
        }
        $this->maxAge = $maxAge;
        return $this;
    }

    /**
     * 設定されている有効期間を取得します。
     *
     * @return int 有効期間の秒数 (未設定時はデフォルトの 1800)
     */
    public function getMaxAge(): int
    {
        return $this->maxAge ?? 1800;
    }

    /**
     * ガベージコレクション (期限切れセッションの削除) を実行する確率を設定します。
     * 0 以上 1 以下の小数を指定します。0 の場合は実行されず、1 の場合は常に実行されます。
     *
     * @param float $p 0 以上 1 以下の小数
     * @return SessionStorageBuilder このオブジェクト自身
     * @throws InvalidArgumentException 範囲外の値が指定された場合
     */
    public function setGcProbability(float $p): self
    {
        if ($p < 0.0 || 1.0 < $p) {
            throw new InvalidArgumentException("Invalid GC probability value: {$p}");
        }
        $this->gcProbaility = $p;
        return $this;
    }

    /**
     * 設定されているガベージコレクションの実行確率を返します。
     *
     * @return float 実行確率 (未指定時は 0.0)
     */
    public function getGcProbability(): float
    {
        return $this->gcProbaility ?? 0.0;
    }

    /**
     * 時刻計算に使用する Clock オブジェクトを設定します。
     *
     * @param Clock $clock Clock オブジェクト
     * @return SessionStorageBuilder このオブジェクト自身
     */
    public function setClock(Clock $clock): self
    {
        $this->clock = $clock;
        return $this;
    }

    /**
     * 設定されている Clock オブジェクトを取得します。
     *
     * @return Clock クロックオブジェクト (未設定時は DefaultClock)
     */
    public function getClock(): Clock
    {
        return $this->clock ?? DefaultClock::getInstance();
    }

    /**
     * セッション ID 生成などに使用する乱数生成器を設定します。
     *
     * @param Random $random 乱数生成器オブジェクト
     * @return SessionStorageBuilder このオブジェクト自身
     */
    public function setRandom(Random $random): self
    {
        $this->random = $random;
        return $this;
    }

    /**
     * 設定されている乱数生成器を取得します。
     *
     * @return Random 乱数生成器オブジェクト (未設定時はデフォルトの乱数生成器)
     */
    public function getRandom(): Random
    {
        return $this->random ?? DefaultRandom::getInstance();
    }

    /**
     * このビルダーの設定内容に基づいて SessionStorage インスタンスを構築します。
     *
     * @return SessionStorage 構築されたストレージオブジェクト
     */
    public function build(): SessionStorage
    {
        return SessionStorage::newInstance($this);
    }
}
