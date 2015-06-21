<?php

namespace SMB\Benchmark;

/**
 * 簡易ベンチマーク計測結果フォーマット用
 *
 * @author shimabox.net
 */
class Formatter implements IFormatter
{
    /**
     * echo用フォーマット
     *
     * @param string $mark
     * @param float $benchmark
     * @return string
     */
    public function forEcho($mark, $benchmark)
    {
        return '<pre>benchmark => ' . $mark . ' : ' . $benchmark . '秒</pre>' . PHP_EOL;
    }
}
