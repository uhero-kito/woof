<?php

namespace Woof\Web\Session;

use InvalidArgumentException;
use LogicException;
use Woof\Http\Request;
use Woof\Web\Session;
use Woof\System\Clock;
use Woof\System\Random;

/**
 * SessionContainer と連携し、セッションの生成・取得・保存・ガベージコレクションを管理するクラスです。
 */
class SessionStorage
{
    /**
     * セッションの永続化を行うコンテナオブジェクトです。
     *
     * @var SessionContainer
     */
    private $container;

    /**
     * クライアント側でセッション ID を保持するための Cookie 名です。
     *
     * @var string
     */
    private $key;

    /**
     * セッションの有効期間 (秒数) です。
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
     * セッション ID の生成等に使用する乱数生成器です。
     *
     * @var Random
     */
    private $random;

    /**
     * メモリ上に展開・キャッシュされた Session オブジェクトの連想配列です。
     *
     * @var Session[]
     */
    private $sessions;

    /**
     * このクラスは SessionStorageBuilder を使用して初期化します。
     */
    private function __construct()
    {
        $this->sessions = [];
    }

    /**
     * SessionStorageBuilder の状態を元に、新しい SessionStorage インスタンスを生成します。
     * このメソッドは SessionStorageBuilder::build() から参照されます。
     *
     * @param SessionStorageBuilder $builder 構築済みのビルダーオブジェクト
     * @return SessionStorage 生成された SessionStorage オブジェクト
     * @throws LogicException SessionContainer またはセッションキーが指定されていない場合
     */
    public static function newInstance(SessionStorageBuilder $builder): self
    {
        if (!$builder->hasSessionContainer()) {
            throw new LogicException("SessionContainer is not specified");
        }
        if (!strlen($key = $builder->getKey())) {
            throw new LogicException("Session key is not specified");
        }

        $instance                = new self();
        $instance->container     = $builder->getSessionContainer();
        $instance->key           = $key;
        $instance->maxAge        = $builder->getMaxAge();
        $instance->gcProbability = $builder->getGcProbability();
        $instance->clock         = $builder->getClock();
        $instance->random        = $builder->getRandom();
        return $instance;
    }

    /**
     * 設定されている SessionContainer を取得します。
     *
     * @return SessionContainer 設定されている SessionContainer オブジェクト
     */
    public function getSessionContainer(): SessionContainer
    {
        return $this->container;
    }

    /**
     * 設定されているセッションキー (Cookie 名) を取得します。
     *
     * @return string 設定されているセッションキー (Cookie 名)
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * 設定されているセッションの有効期間 (秒数) を取得します。
     *
     * @return int セッションの有効時間 (秒数)
     */
    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * 設定されているガベージコレクションの実行確率を 0 以上 1 以下の小数で取得します。
     *
     * @return float ガベージコレクションの実行確率 (0 以上 1 以下)
     */
    public function getGcProbability(): float
    {
        return $this->gcProbability;
    }

    /**
     * 指定された HTTP リクエストに紐付けられたセッションを取得します。
     * もしも HTTP リクエストで指定されたセッション ID が無効、期限切れ、または未設定だった場合、
     * 新しいセッション ID を生成してその結果を返します。
     *
     * @param Request $request 対象の HTTP リクエスト
     * @return Session リクエストに紐づくセッションオブジェクト
     */
    public function getSession(Request $request): Session
    {
        $id = $request->getCookie($this->key);
        return Session::validateId($id) ? ($this->sessions[$id] ?? $this->fetchSession($id)) : $this->newSession();
    }

    /**
     * コンテナから指定された ID のセッションをロードし、メモリにキャッシュします。
     * 確率に応じてガベージコレクションの実行や期限切れ ID の再採番を行います。
     *
     * @param string $id セッション ID
     * @return Session 生成または復元されたセッションオブジェクト
     */
    private function fetchSession(string $id): Session
    {
        $maxAge    = $this->maxAge;
        $container = $this->container;
        if ($this->determineGC()) {
            $container->cleanExpiredSessions($maxAge);
        }

        $contains = $container->contains($id, $maxAge);
        $fixedId  = $contains ? $id : $this->generateId();
        $isNew    = !$contains;
        $data     = $container->load($fixedId);
        $session  = new Session($fixedId, $data, $isNew);

        $this->sessions[$id] = $session;
        return $session;
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
     * 指定された ID のセッションを取得します。
     * もしも対象のセッションが存在しない場合、引数のセッション ID でセッションを初期化します。
     *
     * @param string $id セッション ID
     * @return Session 引数のセッション ID を持つ Session オブジェクト
     * @throws InvalidArgumentException 指定された ID の書式が不正な場合
     */
    public function getSessionById($id)
    {
        if (!Session::validateId($id)) {
            throw new InvalidArgumentException("Invalid session ID: '{$id}'");
        }

        $maxAge    = $this->maxAge;
        $container = $this->container;
        $container->cleanExpiredSessions($maxAge);
        $isNew     = !$container->contains($id, $maxAge);
        $data      = $container->load($id);
        return new Session($id, $data, $isNew);
    }

    /**
     * 指定されたセッションのデータをコンテナに保存します。
     *
     * @param Session $session 保存するセッションオブジェクト
     * @return bool 保存に成功した場合に true
     */
    public function save(Session $session): bool
    {
        return $this->container->save($session->getId(), $session->getAll());
    }

    /**
     * 新しいセッション ID を採番し、空のセッションオブジェクトを生成します。
     *
     * @return Session 新規生成されたセッションオブジェクト
     */
    private function newSession()
    {
        $id     = $this->generateId();
        $result = new Session($id, [], true);

        $this->sessions[$id] = $result;
        return $result;
    }

    /**
     * ハッシュ関数を利用して安全なセッション ID を生成します。
     *
     * @return string 生成されたセッション ID 文字列
     */
    private function generateId()
    {
        return sha1($this->key . $this->clock->getTime() . $this->random->next());
    }
}
