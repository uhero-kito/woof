<?php

namespace Woof\Util;

/**
 * 配列やスカラー値などのプリミティブなデータを DataObject として扱うためのラッパークラスです。
 *
 * DataObject インタフェースを要求するコンポーネント (JsonBody など) において、
 * エンドユーザーが指定した配列や文字列などをそのまま DataObject として内部で取り扱うために使用します。
 */
class RawDataObject implements DataObject
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * ラップする値を指定してオブジェクトを生成します。
     *
     * @param mixed $value DataObject として扱う値 (通常は配列やスカラー値)
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * コンストラクタで指定されたラップ済みの値をそのまま返します。
     *
     * @return mixed 保持している値
     */
    public function toValue()
    {
        return $this->value;
    }
}
