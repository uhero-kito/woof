<?php

namespace Woof\Web\Cache;

use InvalidArgumentException;
use Woof\System\Clock;
use Woof\System\DefaultClock;
use Woof\System\DefaultRandom;
use Woof\System\Random;

/**
 * VariantStorage オブジェクトを構築するためのビルダークラスです。
 */
class VariantStorageBuilder
{
    /**
     * バリアントを保存するコンテナです。
     *
     * @var VariantContainer
     */
    private $container;

    /**
     * キャッシュの有効期限 (秒) です。
     *
     * @var int
     */
    private $maxAge;

    /**
     * 設定するガベージコレクションの実行確率です。
     *
     * @var float
     */
    private $gcProbability;

    /**
     * 設定する Clock オブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 設定する Random オブジェクトです。
     *
     * @var Random
     */
    private $random;

    /**
     * VariantContainer を設定します。
     *
     * @param VariantContainer $container バリアントの保管を行うための VariantContainer オブジェクト
     * @return VariantStorageBuilder このオブジェクト自身
     */
    public function setVariantContainer(VariantContainer $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * VariantContainer が設定されているかを判定します。
     *
     * @return bool 設定されている場合に true
     */
    public function hasVariantContainer(): bool
    {
        return ($this->container !== null);
    }

    /**
     * バリアントの保存先コンテナを取得します。
     *
     * @return VariantContainer
     */
    public function getVariantContainer(): VariantContainer
    {
        return $this->container;
    }

    /**
     * キャッシュの有効期間 (秒数) を設定します。
     *
     * @param int $maxAge 0 より大きい秒数
     * @return VariantStorageBuilder このオブジェクト自身
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
     * @return int 有効期間の秒数 (未設定時はデフォルトの 3600)
     */
    public function getMaxAge(): int
    {
        return $this->maxAge ?? 3600;
    }

    /**
     * ガベージコレクション (期限切れセッションの削除) を実行する確率を設定します。
     * 0 以上 1 以下の小数を指定します。0 の場合は実行されず、1 の場合は常に実行されます。
     *
     * @param float $p 0 以上 1 以下の小数
     * @return VariantStorageBuilder このオブジェクト自身
     * @throws InvalidArgumentException 範囲外の値が指定された場合
     */
    public function setGcProbability(float $p): self
    {
        if ($p < 0.0 || 1.0 < $p) {
            throw new InvalidArgumentException("Invalid GC probability value: {$p}");
        }
        $this->gcProbability = $p;
        return $this;
    }

    /**
     * 設定されているガベージコレクションの実行確率を返します。
     *
     * @return float 実行確率 (未指定時は 0.0)
     */
    public function getGcProbability(): float
    {
        return $this->gcProbability ?? 0.0;
    }

    /**
     * 時刻計算に使用する Clock オブジェクトを設定します。
     *
     * @param Clock $clock Clock オブジェクト
     * @return VariantStorageBuilder このオブジェクト自身
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
     * @return VariantStorageBuilder このオブジェクト自身
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
     * このビルダーの設定内容に基づいて VariantStorage インスタンスを構築します。
     *
     * @return VariantStorage 構築された VariantStorage オブジェクト
     */
    public function build(): VariantStorage
    {
        return VariantStorage::newInstance($this);
    }
}
