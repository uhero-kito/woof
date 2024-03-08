<?php

namespace Woof\Web;

use InvalidArgumentException;

/**
 * HTTP リクエストに紐づく個々のセッションデータを保持するクラスです。
 */
class Session
{
    /**
     * セッション ID をあらわします。
     *
     * @var string
     */
    private $id;

    /**
     * セッションデータを格納する連想配列です。
     *
     * @var array
     */
    private $data;

    /**
     * このリクエストで新規に作成されたセッションかどうかをあらわすフラグです。
     *
     * @var bool
     */
    private $isNew;

    /**
     * セッションデータの値が変更されたかどうかをあらわすフラグです。
     *
     * @var bool
     */
    private $isChanged;

    /**
     * セッション ID とデータを指定して新しい Session インスタンスを生成します。
     *
     * @param string $id セッション ID
     * @param array $data セッションデータの連想配列
     * @param boolean $isNew 新規作成されたセッションの場合は true
     * @throws InvalidArgumentException 不正な形式のセッション ID が指定された場合
     */
    public function __construct(string $id, array $data, bool $isNew = false)
    {
        if (!self::validateId($id)) {
            throw new InvalidArgumentException("Invalid session ID: '{$id}'");
        }
        $this->id        = $id;
        $this->data      = $data;
        $this->isNew     = $isNew;
        $this->isChanged = false;
    }

    /**
     * 文字列がセッション ID として有効な形式か判定します。
     * 半角英数字・ハイフン・カンマのみで構成されている場合に true を返します。
     *
     * @param string $id 判定する文字列
     * @return bool 有効なセッション ID の形式である場合に true
     */
    public static function validateId($id): bool
    {
        return 0 < preg_match("/\\A[0-9a-zA-Z,\\-]+\\z/", $id);
    }

    /**
     * セッション ID を取得します。
     *
     * @return string セッション ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * セッションデータを設定します。
     * このメソッドが呼び出されると isChanged フラグが true になります。
     *
     * @param string $key データキー
     * @param mixed  $value 設定する値
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        $this->isChanged  = true;
    }

    /**
     * 指定されたキーのセッションデータを取得します。
     * 存在しない場合は代替値を返します。
     *
     * @param string $key データキー
     * @param mixed  $defaultValue 存在しない場合の代替値
     * @return mixed 取得した値、または代替値
     */
    public function get($key, $defaultValue = null)
    {
        return $this->data[$key] ?? $defaultValue;
    }

    /**
     * すべてのセッションデータを取得します。
     *
     * @return array セッションデータの連想配列
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * 新規に作成されたセッションかどうかを取得します。
     *
     * @return bool 新規セッションである場合に true
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * セッションデータが変更されたかどうかを取得します。
     *
     * @return bool データが変更された場合に true
     */
    public function isChanged(): bool
    {
        return $this->isChanged;
    }

    /**
     * セッションデータが空であるかを判定します。
     *
     * @return bool データが空の場合に true
     */
    public function isEmpty(): bool
    {
        return !count($this->data);
    }
}
