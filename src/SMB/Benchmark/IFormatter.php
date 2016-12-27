<?php

namespace SMB\Benchmark;

/**
 * 簡易ベンチマーク計測結果フォーマット用interface
 *
 * @author shimabox.net
 */
interface IFormatter
{
    /**
     * echo用フォーマット
     *
     * @param string $mark
     * @param float $benchmark
     * @return string
     */
    public function forEcho($mark, $benchmark);

    /**
     * 計測結果出力用フォーマット
     *
     * @param array $result
     * @return array
     */
    public function forOutput(array $result);
}
