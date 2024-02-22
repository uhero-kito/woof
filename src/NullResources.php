<?php

namespace Woof;

/**
 * リソースを一切持たない空の Resources の実装です。
 * リソースを必要としないコンポーネントに対して、ダミーとして渡す場合などに使用します。
 * このクラスは直接インスタンス化することはできません。 getInstance() を使用してインスタンスを取得してください。
 */
class NullResources implements Resources
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
     * @return NullResources NullResources インスタンス
     */
    public static function getInstance(): self
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
     * 常に false を返します。
     *
     * @param string $key 確認したいリソースのキー名
     * @return bool 常に false
     */
    public function contains(string $key): bool
    {
        return false;
    }

    /**
     * 常に ResourceNotFoundException をスローします。
     *
     * @param string $key 取得したいリソースのキー名
     * @return string 常に例外がスローされるため、実際に値が返されることはありません
     * @throws ResourceNotFoundException 常にスローされます
     */
    public function get(string $key): string
    {
        throw new ResourceNotFoundException("This instance cannot fetch any resources.");
    }
}
