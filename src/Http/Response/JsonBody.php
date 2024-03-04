<?php

namespace Woof\Http\Response;

use Woof\Util\DataObject;
use Woof\Util\RawDataObject;

/**
 * データを JSON 形式にエンコードしてレスポンスボディとして送信するクラスです。
 */
class JsonBody implements Body
{
    /**
     * JSON の元となるデータオブジェクトです。
     *
     * @var DataObject
     */
    private $data;

    /**
     * json_encode() 実行時に適用されるオプション (ビットマスク) です。
     *
     * @var int
     */
    private $encodeOptions;

    /**
     * エンコード済みの JSON 文字列です。
     *
     * @var string
     */
    private $output;

    /**
     * 指定された値を JSON として取り扱う JsonBody オブジェクトを生成します。
     *
     * @param DataObject|array $data JSON 化するデータ (配列を渡した場合は内部で RawDataObject に変換されます)
     * @param int $encodeOptions json_encode() に渡すオプションのビットマスク (デフォルトは 0)
     */
    public function __construct($data, int $encodeOptions = 0)
    {
        $dataObject          = ($data instanceof DataObject) ? $data : new RawDataObject($data);
        $this->data          = $dataObject;
        $this->encodeOptions = $encodeOptions;
        $this->output        = json_encode($dataObject->toValue(), $encodeOptions);
    }

    /**
     * JSON の元となっているデータオブジェクトを取得します。
     *
     * @return DataObject データオブジェクト
     */
    public function getData(): DataObject
    {
        return $this->data;
    }

    /**
     * エンコード時に指定されたオプションのビットマスクを取得します。
     *
     * @return int json_encode() のオプション
     */
    public function getEncodeOptions(): int
    {
        return $this->encodeOptions;
    }

    /**
     * エンコード済みの JSON 文字列を取得します。
     *
     * @return string JSON 文字列
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * エンコード済みの JSON 文字列をクライアントに送信します。
     *
     * @return bool 常に true
     */
    public function sendOutput(): bool
    {
        echo $this->output;
        return true;
    }

    /**
     * 常に "application/json" を返します。
     *
     * @return string "application/json"
     */
    public function getContentType(): string
    {
        return "application/json";
    }

    /**
     * JSON 文字列のバイト数を返します。
     *
     * @return int コンテンツのバイト数
     */
    public function getContentLength(): int
    {
        return strlen($this->output);
    }
}
