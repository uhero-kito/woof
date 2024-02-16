<?php

namespace Woof\Log;

/**
 * 書き込みを一切行わない LogStorage の実装です。 (Null Object パターン)
 *
 * ログ出力を必要としない環境や、副作用 (ファイル入出力など) を排除したいテスト環境において、
 * 呼び出し元のコードを変更することなくログ出力を安全に無効化するために使用します。
 */
class NullLogStorage implements LogStorage
{
    /**
     * 外部からのインスタンス生成を禁止することで getInstance() の使用を強制します。
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * このクラスの唯一のインスタンスを取得します。
     *
     * @return NullLogStorage このクラスの唯一のインスタンス
     */
    public static function getInstance()
    {
        // @codeCoverageIgnoreStart
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        // @codeCoverageIgnoreEnd
        return $instance;
    }

    /**
     * 何もせずに常に true を返します。
     *
     * 実際には書き込みを行いませんが、呼び出し元の処理を正常に継続させるため、
     * 常に書き込みが成功した (true) ものとして振る舞います。
     *
     * @param string $content 出力するログの内容
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return bool 常に true
     */
    public function write(string $content, int $time, int $level): bool
    {
        // noop
        return true;
    }
}
