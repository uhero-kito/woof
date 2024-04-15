<?php

namespace Woof\Web\Cache;

use LogicException;
use Woof\System\Clock;
use Woof\System\Random;
use Woof\Web\ViewBody;

/**
 * キャッシュされた View の内容 (Variant) の出し入れを統括するストレージクラスです。
 * 指定された確率に基づいて、期限切れキャッシュの自動削除 (ガベージコレクション) を行います。
 */
class VariantStorage
{
    /**
     * バリアントの保管を行うコンテナです。
     *
     * @var VariantContainer
     */
    private $container;

    /**
     * キャッシュの有効期限 (秒数) です。
     *
     * @var int
     */
    private $maxAge;

    /**
     * ガベージコレクションが実行される確率です。
     *
     * @var float
     */
    private $gcProbability;

    /**
     * 現在時刻の取得に使用する Clock オブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * ガベージコレクションの確率判定に使用する乱数生成器です。
     *
     * @var Random
     */
    private $random;

    /**
     * メモリ上にキャッシュされた Variant オブジェクトの配列です。
     *
     * @var Variant[]
     */
    private $variants;

    /**
     * このクラスは VariantStorageBuilder を使用して初期化します。
     */
    private function __construct()
    {
        $this->variants = [];
    }

    /**
     * VariantStorageBuilder の情報を元に、新しい VariantStorage インスタンスを生成します。
     *
     * @param VariantStorageBuilder $builder 構築済のビルダーオブジェクト
     * @return VariantStorage 生成された VariantStorage オブジェクト
     * @throws LogicException VariantContainer が設定されていない場合
     */
    public static function newInstance(VariantStorageBuilder $builder): self
    {
        if (!$builder->hasVariantContainer()) {
            throw new LogicException("VariantContainer must be set before building VariantStorage.");
        }

        $instance                = new self();
        $instance->container     = $builder->getVariantContainer();
        $instance->maxAge        = $builder->getMaxAge();
        $instance->gcProbability = $builder->getGcProbability();
        $instance->clock         = $builder->getClock();
        $instance->random        = $builder->getRandom();
        return $instance;
    }

    /**
     * 設定されている VariantContainer を取得します。
     *
     * @return VariantContainer
     */
    public function getVariantContainer(): VariantContainer
    {
        return $this->container;
    }

    /**
     * 設定されているセッションの有効期間 (秒数) を取得します。
     *
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * 設定されているガベージコレクションの実行確率を取得します。
     *
     * @return float
     */
    public function getGcProbability(): float
    {
        return $this->gcProbability;
    }

    /**
     * 乱数を用いて、現在のリクエストでガベージコレクションを実行すべきか判定します。
     *
     * @return bool 実行すべきと判定された場合に true
     */
    private function determineGC(): bool
    {
        $p = $this->gcProbability;
        if ($p === 0.0) {
            return false;
        }
        if ($p === 1.0) {
            return true;
        }
        return ($this->random->next() / mt_getrandmax()) < $p;
    }

    /**
     * 指定された ViewBody オブジェクトに対応するバリアントが有効な状態で存在するかどうかを判定します。
     *
     * @param ViewBody $body 判定対象の ViewBody オブジェクト
     * @return bool 有効なバリアントが存在する場合は true
     */
    public function hasVariant(ViewBody $body): bool
    {
        $maxAge    = $this->maxAge;
        $container = $this->container;
        if ($this->determineGC()) {
            $container->cleanExpiredVariants($maxAge);
        }

        $id = $this->generateId($body);
        return $container->contains($id, $maxAge);
    }

    /**
     * コンテナから指定された ViewBody に対応するバリアントを取得します。
     * キャッシュが存在しないか、または期限切れの場合は ViewBody からコンテンツを生成・保存して返します。
     *
     * @param ViewBody $body 対象の ViewBody オブジェクト
     * @return Variant 取得または新規生成されたバリアントオブジェクト
     */
    public function fetchVariant(ViewBody $body): Variant
    {
        $id = $this->generateId($body);
        if (isset($this->variants[$id])) {
            return $this->variants[$id];
        }

        $maxAge    = $this->maxAge;
        $container = $this->container;
        if ($this->determineGC()) {
            $container->cleanExpiredVariants($maxAge);
        }

        if ($container->contains($id, $maxAge)) {
            $variant = $container->load($id);
        } else {
            $content = $body->getOutput();
            $container->save($id, $content);
            $variant = new Variant($id, $content, $this->clock->getTime());
        }

        $this->variants[$id] = $variant;
        return $variant;
    }

    /**
     * 指定された ViewBody に含まれる View オブジェクトをシリアライズし、sha1 でハッシュ化したバリアント ID を生成します。
     *
     * @param ViewBody $body キャッシュ対象の ViewBody オブジェクト
     * @return string 生成されたハッシュ ID
     */
    public function generateId(ViewBody $body): string
    {
        return sha1(serialize($body->getView()));
    }
}
