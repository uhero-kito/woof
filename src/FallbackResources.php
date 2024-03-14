<?php

namespace Woof;

/**
 * 2 つの Resources オブジェクトを組み合わせ、リソースの取得をフォールバック (代替) するクラスです。
 * このオブジェクトに対するメソッド呼び出しは下記のように処理されます。
 *
 * - 指定されたキーがプライマリ (第 1 候補) に存在する場合: プライマリから結果を返します
 * - 指定されたキーがプライマリに存在しない場合: セカンダリ (第 2 候補) から結果を返します
 * - 指定されたキーがどちらにも存在しない場合: ResourceNotFoundException をスローします
 */
class FallbackResources implements Resources
{
    /**
     * @var Resources
     */
    private $primary;

    /**
     * @var Resources
     */
    private $secondary;

    /**
     * プライマリとセカンダリの Resources オブジェクトを指定してインスタンスを生成します。
     *
     * @param Resources $primary 優先して検索されるプライマリの Resources
     * @param Resources $secondary プライマリに見つからなかった場合に検索されるセカンダリの Resources
     */
    public function __construct(Resources $primary, Resources $secondary)
    {
        $this->primary   = $primary;
        $this->secondary = $secondary;
    }

    /**
     * 指定されたキーのリソースが、プライマリまたはセカンダリのいずれかに存在するかを調べます。
     *
     * @param string $key 確認したいリソースのキー名
     * @return bool いずれかにリソースが存在する場合に true
     */
    public function contains(string $key): bool
    {
        return $this->primary->contains($key) || $this->secondary->contains($key);
    }

    /**
     * 指定されたキーのリソースをプライマリまたはセカンダリから取得します。
     *
     * @param string $key 取得したいリソースのキー名
     * @return string 取得したリソースの内容
     * @throws ResourceNotFoundException プライマリ・セカンダリのどちらにもリソースが存在しない場合
     */
    public function get(string $key): string
    {
        if ($this->primary->contains($key)) {
            return $this->primary->get($key);
        }
        if ($this->secondary->contains($key)) {
            return $this->secondary->get($key);
        }

        throw new ResourceNotFoundException("Resource not found: '{$key}'");
    }
}
