<?php

namespace Woof\Web\Session;

/**
 * PHP のセッションデータ文字列 (シリアライズされた生データ) をパースし、連想配列に復元するクラスです。
 */
class ParserContext
{
    /**
     * 解析対象となるセッションデータの生文字列です。
     *
     * @var string
     */
    private $source;

    /**
     * 現在解析中の文字位置 (オフセット) です。
     *
     * @var int
     */
    private $index;

    /**
     * 解析結果を格納する連想配列です。
     *
     * @var array
     */
    private $result;

    /**
     * 解析対象のセッションデータ文字列を指定してインスタンスを生成します。
     *
     * @param string $source PHP のセッションフォーマットに従った文字列
     */
    public function __construct(string $source)
    {
        $this->source = $source;
        $this->index  = 0;
        $this->result = [];
    }

    /**
     * 文字列の終端に達するまでパース処理を実行し、結果の配列を返します。
     *
     * @return array 復元されたセッションデータの連想配列
     * @throws ParseException フォーマットが不正な場合
     */
    public function parse(): array
    {
        $length = strlen($this->source);
        while ($this->index < $length) {
            $this->next();
        }
        return $this->result;
    }

    /**
     * 次のキーと値のペアを解析し、結果配列に格納します。
     *
     * @throws ParseException キーの形式が不正な場合
     */
    private function next(): void
    {
        $current = substr($this->source, $this->index);
        $matched = [];
        if (!preg_match("/\\A([^|]+)\\|/", $current, $matched)) {
            throw new ParseException("Invalid session format");
        }

        $this->index += strlen($matched[0]);
        $key   = $matched[1];
        $value = $this->unserialize();
        $this->result[$key] = $value;
    }

    /**
     * 現在のインデックス位置から値を1つアンシリアライズします。
     * null, boolean, int, float, string, array のいずれかの型に復元されます。
     *
     * @return mixed 復元された値
     * @throws ParseException 値の形式が不正な場合
     */
    private function unserialize()
    {
        $current = substr($this->source, $this->index);
        $matched = [];
        if (substr($current, 0, 2) === "N;") {
            $this->index += 2;
            return null;
        }
        if (substr($current, 0, 4) === "b:0;") {
            $this->index += 4;
            return false;
        }
        if (substr($current, 0, 4) === "b:1;") {
            $this->index += 4;
            return true;
        }
        if (preg_match("/\\Ai:([0-9\\-]+);/", $current, $matched)) {
            $this->index += strlen($matched[0]);
            return (int) $matched[1];
        }
        if (preg_match("/\\Ad:([0-9\\.\\-]+);/", $current, $matched)) {
            $this->index += strlen($matched[0]);
            return (float) $matched[1];
        }
        if (preg_match("/\\As:([0-9]+):/", $current, $matched)) {
            $this->index += strlen($matched[0]);
            $length = $matched[1];
            $result = substr($this->source, $this->index, $length + 3);
            if (substr($result, 0, 1) !== '"' || substr($result, -2) !== '";') {
                throw new ParseException("Invalid session format");
            }
            $this->index += $length + 3;
            return substr($result, 1, -2);
        }
        if (preg_match("/\\Aa:([0-9]+):{/", $current, $matched)) {
            $this->index += strlen($matched[0]);
            $count = (int) $matched[1];
            $result = [];
            for ($i = 0; $i < $count; $i++) {
                $key   = $this->unserialize();
                $value = $this->unserialize();
                $result[$key] = $value;
            }
            $this->index++;
            return $result;
        }

        throw new ParseException("Invalid session format (index:{$this->index})");
    }
}
