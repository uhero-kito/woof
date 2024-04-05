<?php

namespace Woof\Web\Cache;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Cache\VariantContainerHelper
 */
class VariantContainerHelperTest extends TestCase
{
    /**
     * バリアント ID と末尾文字列からファイル名 (またはキー名) が正しく生成されることを確認します。
     *
     * @param string $id バリアント ID
     * @param string $suffix 末尾文字列
     * @param string $expected 期待されるファイル名
     * @covers ::formatFilename
     * @dataProvider provideTestFormatFilename
     */
    public function testFormatFilename(string $id, string $suffix, string $expected): void
    {
        $obj = new VariantContainerHelper();
        $this->assertSame($expected, $obj->formatFilename($id, $suffix));
    }

    /**
     * testFormatFilename() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormatFilename(): array
    {
        return [
            ["1234567890abcdef", ".dat", "1234567890abcdef.dat"],
            ["1357924680bbbbbb", "", "1357924680bbbbbb"],
            ["variant", "_cache", "variant_cache"],
        ];
    }

    /**
     * キーの一覧から、指定された末尾文字列を持つものだけが正しく抽出されることを確認します。
     *
     * @covers ::filterVariantKeys
     */
    public function testFilterVariantKeys(): void
    {
        $obj   = new VariantContainerHelper();
        $input = [
            "cache/12345.dat",
            "cache/67890.dat",
            "dummy_file.txt",
            "cache/not_a_dat_file",
            "abcdef.dat",
        ];

        $generator1 = $obj->filterVariantKeys($input, ".dat");
        $result1    = iterator_to_array($generator1, false);
        $expected1  = [
            "cache/12345.dat",
            "cache/67890.dat",
            "abcdef.dat",
        ];
        $this->assertSame($expected1, $result1);

        $generator2 = $obj->filterVariantKeys($input, "");
        $result2    = iterator_to_array($generator2, false);
        $this->assertSame($input, $result2);
    }

    /**
     * キャッシュデータが有効期限切れかどうかが正しく判定されることを確認します。
     *
     * @param int $mtime 最終更新日時
     * @param int $maxAge 有効期限 (秒)
     * @param int $now 現在時刻
     * @param bool $expected 期待される判定結果 (true: 期限切れ, false: 有効)
     * @covers ::checkExpired
     * @dataProvider provideTestCheckExpired
     */
    public function testCheckExpired(int $mtime, int $maxAge, int $now, bool $expected): void
    {
        $obj = new VariantContainerHelper();
        $this->assertSame($expected, $obj->checkExpired($mtime, $maxAge, $now));
    }

    /**
     * testCheckExpired() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestCheckExpired(): array
    {
        return [
            // $now が境界値より大きい場合は true (期限切れ)
            [1500000000, 3600, 1500003601, true],
            // $now が境界値とちょうど同じ場合は false (有効, 境界値)
            [1500000000, 3600, 1500003600, false],
            // $now がそれより小さい場合は false (有効)
            [1500000000, 3600, 1500003599, false],
        ];
    }
}
