<?php

namespace Woof\Web\Cache;

use InvalidArgumentException;

/**
 * キャッシュされた View の内容 (バリアント) を表現するイミュータブルな値オブジェクトです。
 */
class Variant
{
    /**
     * バリアントの一意な ID (主に sha-1 ハッシュ) です。
     *
     * @var string
     */
    private $id;

    /**
     * キャッシュされたコンテンツの内容です。
     *
     * @var string
     */
    private $content;

    /**
     * コンテンツの最終更新日時 (Unix time) です。
     *
     * @var int
     */
    private $mtime;

    /**
     * 指定された ID, 内容, 最終更新日時を持つ Variant オブジェクトを構築します。
     *
     * @param string $id      バリアント ID (半角小文字の英数字のみ)
     * @param string $content キャッシュされたコンテンツ内容
     * @param int    $mtime   最終更新日時 (Unix time)
     * @throws InvalidArgumentException ID の形式が不正な場合
     */
    public function __construct(string $id, string $content, int $mtime)
    {
        if (!preg_match("/\\A[a-z0-9]+\\z/", $id)) {
            throw new InvalidArgumentException("Invalid variant ID format: '{$id}'");
        }

        $this->id      = $id;
        $this->content = $content;
        $this->mtime   = $mtime;
    }

    /**
     * このバリアントの ID を取得します。
     *
     * @return string 保持しているバリアントの ID をあらわす文字列
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * キャッシュされたコンテンツ内容を取得します。
     *
     * @return string 保持しているキャッシュコンテンツの文字列
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * 最終更新日時を取得します。
     *
     * @return int コンテンツが保存された日時をあらわす Unix time
     */
    public function getLastModified(): int
    {
        return $this->mtime;
    }
}
