<?php

namespace Woof\Http;

/**
 * HTTP レスポンスの出力を「名前を付けて保存」させる (ダウンロードを促す) ために使用する HeaderField の実装です。
 */
class ContentDisposition implements HeaderField
{
    /**
     * クライアント側で保存される際に提案するファイル名です。
     *
     * @var string
     */
    private $filename;

    /**
     * 保存時のファイル名を指定して ContentDisposition インスタンスを生成します。
     * 引数を省略した場合または空文字列を指定した場合は、ファイル名を指定しない Content-Disposition となります。
     *
     * @param string $filename 保存時の提案ファイル名 (デフォルトは空文字列)
     */
    public function __construct(string $filename = "")
    {
        $this->filename = $filename;
    }

    /**
     * この Content-Disposition の値を HTTP ヘッダーとして出力可能な形式に書式化します。
     * ファイル名が指定されている場合は URL エンコードを行い 'attachment; filename="{filename}"' 形式の文字列を返します。
     * ファイル名が存在しない場合は 'attachment' を返します。
     *
     * @return string フォーマットされたヘッダー値の文字列
     */
    public function format(): string
    {
        if (!strlen($this->filename)) {
            return "attachment";
        }

        $filename = rawurlencode($this->filename);
        return "attachment; filename=\"{$filename}\"";
    }

    /**
     * 文字列 "Content-Disposition" を返します。
     *
     * @return string ヘッダー名 ("Content-Disposition")
     */
    public function getName(): string
    {
        return "Content-Disposition";
    }

    /**
     * 設定されているファイル名を返します。
     *
     * @return string ファイル名 (未指定の場合は空文字列)
     */
    public function getValue()
    {
        return $this->filename;
    }
}
