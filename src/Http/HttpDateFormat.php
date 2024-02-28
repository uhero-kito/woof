<?php

namespace Woof\Http;

use Woof\System\Clock;
use Woof\System\DefaultClock;
use InvalidArgumentException;

/**
 * HTTP-date 形式のヘッダー値の読み書きを行うクラスです。
 */
class HttpDateFormat
{
    /**
     * 曜日の短縮名のリスト (RFC 822, ANSI C 形式用) です。
     *
     * @var array
     */
    const SHORT_DAYS = [
        0 => "Sun",
        1 => "Mon",
        2 => "Tue",
        3 => "Wed",
        4 => "Thu",
        5 => "Fri",
        6 => "Sat",
    ];

    /**
     * 曜日の完全な名前のリスト (RFC 850 形式用) です。
     *
     * @var array
     */
    const LONG_DAYS = [
        0 => "Sunday",
        1 => "Monday",
        2 => "Tuesday",
        3 => "Wednesday",
        4 => "Thursday",
        5 => "Friday",
        6 => "Saturday",
    ];

    /**
     * 月の短縮名のリストです。
     *
     * @var array
     */
    const MONTHS = [
        1  => "Jan",
        2  => "Feb",
        3  => "Mar",
        4  => "Apr",
        5  => "May",
        6  => "Jun",
        7  => "Jul",
        8  => "Aug",
        9  => "Sep",
        10 => "Oct",
        11 => "Nov",
        12 => "Dec",
    ];

    /**
     * 年の計算基準となる Clock オブジェクトです。
     *
     * @var Clock
     */
    private $clock;

    /**
     * 基準となる Clock オブジェクトを指定してインスタンスを生成します。
     *
     * @param Clock|null $clock 未指定の場合は DefaultClock が使用されます
     */
    public function __construct(Clock $clock = null)
    {
        $this->clock = $clock ?? DefaultClock::getInstance();
    }

    /**
     * 指定された HTTP-date 形式の文字列を Unix time (整数) に変換します。
     * RFC 822, RFC 850, ANSI C の 3 つのフォーマットに対応しています。
     *
     * @param string $format 解析する HTTP-date 形式の文字列
     * @return int 変換された Unix time
     * @throws InvalidArgumentException サポートされていないフォーマットの文字列が指定された場合
     */
    public function parse(string $format): int
    {
        if (-1 !== ($rfc822 = $this->parseRfc822($format))) {
            return $rfc822;
        }
        if (-1 !== ($rfc850 = $this->parseRfc850($format))) {
            return $rfc850;
        }
        if (-1 !== ($ansi = $this->parseAnsi($format))) {
            return $ansi;
        }
        throw new InvalidArgumentException("Invalid format: '{$format}'");
    }

    /**
     * RFC 822 形式の文字列を Unix time に変換します。
     *
     * @param string $format 解析する文字列
     * @return int 解析に成功した場合は Unix time、失敗した場合は -1
     */
    private function parseRfc822(string $format): int
    {
        $days    = implode("|", self::SHORT_DAYS);
        $months  = implode("|", self::MONTHS);
        $matched = [];
        if (preg_match("/\\A({$days}), ([0-3][0-9]) ({$months}) ([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT\\z/", $format, $matched)) {
            $year   = (int) $matched[4];
            $month  = array_search($matched[3], self::MONTHS);
            $day    = (int) $matched[2];
            $hour   = (int) $matched[5];
            $minute = (int) $matched[6];
            $second = (int) $matched[7];
            return gmmktime($hour, $minute, $second, $month, $day, $year);
        } else {
            return -1;
        }
    }

    /**
     * RFC 850 形式の文字列を Unix time に変換します。
     *
     * @param string $format 解析する文字列
     * @return int 解析に成功した場合は Unix time、失敗した場合は -1
     */
    private function parseRfc850(string $format): int
    {
        $days    = implode("|", self::LONG_DAYS);
        $months  = implode("|", self::MONTHS);
        $matched = [];
        if (preg_match("/\\A({$days}), ([0-3][0-9])-({$months})-([0-9]{2}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT\\z/", $format, $matched)) {
            $shortY = (int) $matched[4];
            $year   = $this->calculateFullYear($shortY);
            $month  = array_search($matched[3], self::MONTHS);
            $day    = (int) $matched[2];
            $hour   = (int) $matched[5];
            $minute = (int) $matched[6];
            $second = (int) $matched[7];
            return gmmktime($hour, $minute, $second, $month, $day, $year);
        } else {
            return -1;
        }
    }

    /**
     * ANSI C (asctime) 形式の文字列を Unix time に変換します。
     *
     * @param string $format 解析する文字列
     * @return int 解析に成功した場合は Unix time、失敗した場合は -1
     */
    private function parseAnsi(string $format): int
    {
        $days    = implode("|", self::SHORT_DAYS);
        $months  = implode("|", self::MONTHS);
        $matched = [];
        if (preg_match("/^({$days}) ({$months}) ([0-3 ][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) ([0-9]{4})$/", $format, $matched)) {
            $year   = (int) $matched[7];
            $month  = array_search($matched[2], self::MONTHS);
            $day    = (int) trim($matched[3]);
            $hour   = (int) $matched[4];
            $minute = (int) $matched[5];
            $second = (int) $matched[6];
            return gmmktime($hour, $minute, $second, $month, $day, $year);
        } else {
            return -1;
        }
    }

    /**
     * 2 桁で表現された年を、現在の基準時刻をもとに 4 桁の年に変換します。
     *
     * @param int $y 2 桁の年
     * @return int 4 桁に補正された年
     */
    private function calculateFullYear(int $y): int
    {
        $currentYear = (int) date("Y", $this->clock->getTime());
        $century     = (int) ($currentYear / 100);
        $smallY      = $currentYear % 100;
        $resultC     = ($smallY < $y) ? $century - 1 : $century;
        return $resultC * 100 + $y;
    }

    /**
     * 指定された Unix time を RFC 822 に準拠した HTTP-date 形式の文字列 (GMT) に変換します。
     *
     * @param int $time 変換元の Unix time
     * @return string フォーマットされた HTTP-date 文字列
     */
    public function format(int $time): string
    {
        $n = (int) gmdate("n", $time);
        $w = (int) gmdate("w", $time);

        $year  = gmdate("Y", $time);
        $month = self::MONTHS[$n];
        $date  = gmdate("d", $time);
        $day   = self::SHORT_DAYS[$w];
        $hours = gmdate("H:i:s", $time);
        return "{$day}, {$date} {$month} {$year} {$hours} GMT";
    }
}
