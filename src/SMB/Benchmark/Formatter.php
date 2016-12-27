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
        if (PHP_SAPI === 'cli') {
            return 'benchmark => ' . $mark . ' : ' . $benchmark . PHP_EOL;
        }

        return '<pre>benchmark => ' . $mark . ' : ' . $benchmark . '</pre>';
    }

    /**
     * 計測結果出力用フォーマット
     *
     * @param array $result
     * @return array
     */
    public function forOutput(array $result)
    {
        $ret = array();
        foreach ($result as $k => $v) {
            $ret[] = array('label' => $k, 'benchmark' => $v);
        }

        return $ret;
    }
}
