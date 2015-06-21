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
}
