<?php

/**
 * test of SMB\Benchmark
 *
 * PHP version 5 >= 5.4
 *
 * @group  SMBBenchmark
 * @author shimabox.net
 */
class BenchmarkTest extends \TestCaseBase
{
    public function setUp()
    {
        parent::setUp();

        SMB\Benchmark::clear();
    }

    /**
     * シングルトンであること
     * @test
     */
    public function シングルトンである()
    {
        $bm1 = SMB\Benchmark::getInstance();
        $bm2 = SMB\Benchmark::getInstance();
        $bm2->setScale(1);

        $this->assertSame($bm1, $bm2); // 同じオブジェクトである
    }

    /**
     * オブジェクトの初期化ができる
     * @test
     */
    public function オブジェクトの初期化ができる()
    {
        $bm1 = SMB\Benchmark::getInstance();

        SMB\Benchmark::clear();

        $bm2 = SMB\Benchmark::getInstance();

        $this->assertNotSame($bm1, $bm2); // 違うオブジェクトである
    }

    /**
     * 測定結果を取得できる
     * @test
     */
    public function 測定結果を取得できる()
    {
        $bm = SMB\Benchmark::getInstance();
        $bm->start('bench1');
        usleep(15000); // 0.015秒
        $bm->end('bench1');

        $bm->start('bench2');
        usleep(20000); // 0.02秒
        $bm->end('bench2');

        // 少なくとも0.015秒以上経っているはず
        $this->assertTrue($bm->result('bench1') >= 0.015);

        // 少なくとも0.02秒以上経っているはず
        $this->assertTrue($bm->result('bench2') >= 0.02);
    }

    /**
     * 入れ子でも測定結果を取得できる
     * @test
     */
    public function 入れ子でも測定結果を取得できる()
    {
        $bm = SMB\Benchmark::getInstance();

        $bm->start('bench1');

        $bm->start('bench2');
        usleep(15000); // 0.015秒
        $bm->end('bench2');

        $bm->start('bench3');
        usleep(20000); // 0.02秒
        $bm->end('bench3');

        $bm->end('bench1');

        // 少なくとも0.035秒以上経っているはず
        $this->assertTrue($bm->result('bench1') >= 0.035);

        // 少なくとも0.015秒以上経っているはず
        $this->assertTrue($bm->result('bench2') >= 0.015);
        // 0.035秒より処理時間が短い事を期待する
        $this->assertTrue($bm->result('bench2') < 0.035);

        // 少なくとも0.02秒以上経っているはず
        $this->assertTrue($bm->result('bench3') >= 0.02);
        // 0.035秒より処理時間が短い事を期待する
        $this->assertTrue($bm->result('bench3') < 0.035);
    }

    /**
     * すべての測定結果を取得できる
     * @test
     */
    public function すべての測定結果を取得できる()
    {
        $bm = SMB\Benchmark::getInstance();
        $bm->start('bench1');
        usleep(15000); // 0.015秒
        $bm->end('bench1');
        $bm->start('bench2');
        usleep(20000); // 0.02秒
        $bm->end('bench2');

        $actual = $bm->resultAll();

        $this->assertTrue(isset($actual['bench1']));
        $this->assertTrue($actual['bench1'] >= 0.015); // 少なくとも0.015秒以上経っているはず

        $this->assertTrue(isset($actual['bench2']));
        $this->assertTrue($actual['bench2'] >= 0.02); // 少なくとも0.02秒以上経っているはず
    }

    /**
     * 入れ子でもすべての測定結果を取得できる
     * @test
     */
    public function 入れ子でもすべての測定結果を取得できる()
    {
        $bm = SMB\Benchmark::getInstance();

        $bm->start('bench1');

        $bm->start('bench2');
        usleep(15000); // 0.015秒
        $bm->end('bench2');

        $bm->start('bench3');
        usleep(20000); // 0.02秒
        $bm->end('bench3');

        $bm->end('bench1');

        $actual = $bm->resultAll();

        $this->assertTrue(isset($actual['bench1']));
        // 少なくとも0.035秒以上経っているはず
        $this->assertTrue($actual['bench1'] >= 0.035);

        $this->assertTrue(isset($actual['bench2']));
        // 少なくとも0.015秒以上経っているはず
        $this->assertTrue($actual['bench2'] >= 0.015);
        // 0.035秒より処理時間が短い事を期待する
        $this->assertTrue($bm->result('bench2') < 0.035);

        $this->assertTrue(isset($actual['bench3']));
        // 少なくとも0.02秒以上経っているはず
        $this->assertTrue($actual['bench3'] >= 0.02);
        // 0.035秒より処理時間が短い事を期待する
        $this->assertTrue($bm->result('bench3') < 0.035);
    }

    /**
     * 関数の測定結果を取得できる
     * @test
     */
    public function 関数の測定結果を取得できる()
    {
        $actual = SMB\Benchmark::getInstance()
                ->measure(function() {
                    usleep(30000); // 0.03秒
                }, array(), 'bench1')
                ->result('bench1')
        ;

        // 少なくとも0.03秒以上経っているはず
        $this->assertTrue($actual >= 0.03);
    }

    /**
     * オブジェクト関数の測定結果を取得できる
     * @test
     */
    public function オブジェクト関数の測定結果を取得できる()
    {
        $obj = new Hoge();

        $bm = SMB\Benchmark::getInstance();
        $bm->measure(array($obj, 'callbackMethod'), array(), 'bench1');
        $bm->measure(array($obj, 'callbackMethod2'), array(100000), 'bench2');
        $bm->measure(array($obj, 'callbackMethod3'), array(10000, 2), 'bench3');

        // 静的メソッド
        $bm->measure(array('Hoge', 'staticCallbackMethod'), array(200000), 'bench4');

        // 少なくとも0.01秒以上経っているはず
        $actual1 = $bm->result('bench1');
        $this->assertTrue($actual1 >= 0.01 && $actual1 < 0.1);
        // 少なくとも0.1秒以上経っているはず
        $actual2 = $bm->result('bench2');
        $this->assertTrue($actual2 >= 0.1 && $actual2 < 0.2);
        // 少なくとも0.02秒以上経っているはず
        $actual3 = $bm->result('bench3');
        $this->assertTrue($actual3 >= 0.02 && $actual3 < 0.1);
        // 少なくとも0.2秒以上経っているはず
        $actual4 = $bm->result('bench4');
        $this->assertTrue($actual4 >= 0.2 && $actual4 < 0.3);
    }

    /**
     * callable以外がmeasureに渡されたらException
     * @test
     */
    public function callable以外がmeasureに渡されたらException()
    {
        try {
            SMB\Benchmark::getInstance()
                ->measure(array('Piyo', 'test'), array(), 'bench1')
                ->result('bench1')
                ;

            $this->fail(); // 例外が発生しなければ失敗

        } catch (\Exception $e) {

        }
    }

    /**
     * formatterは期待値を受け取っている
     * @test
     */
    public function formatterは期待値を受け取っている()
    {
        // モック化
        $formatter = $this->getMockBuilder('SMB\Benchmark\Formatter')
                ->setMethods(array('forEcho'))
                ->getMock()
                ;

        // 振る舞い指定
        $formatter->expects($this->any())
            ->method('forEcho')
            ->with(
                $this->equalTo('bench1'),
                $this->greaterThan(0.03)
            )
            ;

        // test
        SMB\Benchmark::getInstance()
            ->setFormatter($formatter)
            ->measure(function() {
                usleep(30000); // 0.03秒
            }, array(), 'bench1')
            ->echoResult('bench1')
            ;
    }

    /**
     * formatterは複数出力でも期待値を受け取っている
     * @test
     */
    public function formatterは複数出力でも期待値を受け取っている()
    {
        // モック化
        $formatter = $this->getMockBuilder('SMB\Benchmark\Formatter')
            ->setMethods(array('forEcho'))
            ->getMock()
        ;

        // 振る舞い指定
        $formatter->expects($this->any())
            ->method('forEcho')
            ->withConsecutive(
                array($this->equalTo('bench1'), $this->greaterThan(0.03)),
                array($this->equalTo('bench2'), $this->greaterThan(0.04))
            )
            ;

        // test
        SMB\Benchmark::getInstance()
            ->setFormatter($formatter)
            ->measure(function() {
                usleep(30000); // 0.03秒
            }, array(), 'bench1')
            ->measure(function() {
                usleep(40000); // 0.04秒
            }, array(), 'bench2')
            ->echoResultAll()
            ;
    }

    /**
     * 出力結果の桁数が変更できる
     * @test
     */
    public function 出力結果の桁数が変更できる()
    {
        $bm = SMB\Benchmark::getInstance();

        $ret1 = $bm->start('bench1')
                    ->measure(function() {
                        usleep(1);
                    })
                    ->end('bench1')
                    ->result('bench1')
                    ;

        $actual1 = strlen($ret1) - 2; // 0. の部分を引く
        $this->assertEquals($actual1, 6); // デフォルトは6桁

        $ret2 = $bm->setScale(4) // 4桁に変更
                    ->start('bench2')
                    ->measure(function() {
                        usleep(1);
                    })
                    ->end('bench2')
                    ->result('bench2')
                    ;

        $actual2 = strlen($ret2) - 2;
        $this->assertEquals($actual2, 4);
    }

    /**
     * 出力結果の桁数設定で1より小さい値を設定したら桁数はデフォルトの6桁になる
     * @test
     */
    public function 出力結果の桁数設定で1より小さい値を設定したら桁数はデフォルトの6桁になる()
    {
        $bm  = SMB\Benchmark::getInstance();
        $ret = $bm->setScale(0) // 1以下を指定
                    ->start('bench1')
                    ->measure(function() {
                        usleep(1);
                    })
                    ->end('bench1')
                    ->result('bench1')
                    ;

        $actual = strlen($ret) - 2;
        $this->assertEquals($actual, 6); // デフォルトの6桁になる
    }

    /**
     * マーキングされていなければ測定結果は空が返る
     * @test
     */
    public function マーキングされていなければ測定結果は空が返る()
    {
        $bm = SMB\Benchmark::getInstance();

        $bm->start('bench1');
        usleep(1);
        $bm->end('bench1');

        $actual = $bm->result('bench2');

        $this->assertTrue($actual === '');
    }

    /**
     * NULLでマーキングされていたら測定結果は空が返る
     * @test
     */
    public function NULLでマーキングされていたら測定結果は空が返る()
    {
        $bm = SMB\Benchmark::getInstance();

        $bm->start(null);
        usleep(1);
        $bm->end(null);

        $actual = $bm->result(null);

        $this->assertTrue($actual === '');
    }

    /**
     * 空文字でマーキングされていたら測定結果は空が返る
     * @test
     */
    public function 空文字でマーキングされていたら測定結果は空が返る()
    {
        $bm = SMB\Benchmark::getInstance();

        $bm->start('');
        usleep(1);
        $bm->end('');

        $actual = $bm->result('');

        $this->assertTrue($actual === '');
    }

    /**
     * 配列でマーキングされていたら測定結果は空が返る
     * @test
     */
    public function 配列でマーキングされていたら測定結果は空が返る()
    {
        $arr = array();

        $bm = SMB\Benchmark::getInstance();

        $bm->start($arr);
        usleep(1);
        $bm->end($arr);

        $actual = $bm->result($arr);

        $this->assertTrue($actual === '');
    }

    /**
     * オブジェクトでマーキングされていたら測定結果は空が返る
     * @test
     */
    public function オブジェクトでマーキングされていたら測定結果は空が返る()
    {
        $obj = new stdClass();

        $bm = SMB\Benchmark::getInstance();

        $bm->start($obj);
        usleep(1);
        $bm->end($obj);

        $actual = $bm->result($obj);

        $this->assertTrue($actual === '');
    }

    /**
     * 繰り返し指定されていたら指定された回数だけ測定対象のメソッドが呼ばれている
     * @test
     */
    public function 繰り返し指定されていたら指定された回数だけ測定対象のメソッドが呼ばれている()
    {
        // モック
        $mock = $this->getMockBuilder('mock')
            ->setMethods(array('test1', 'test2'))
            ->getMock()
        ;

        // mock->test1() 2回呼ばれていること
        $mock->expects($this->exactly(2))->method('test1');
        // mock->test2() 10回呼ばれていること
        $mock->expects($this->exactly(10))->method('test2');

        SMB\Benchmark::getInstance()
            ->measure(array($mock, 'test1'), array(), 'bench1', 2)
            ->measure(array($mock, 'test2'), array(), 'bench2', 10)
        ;
    }

    /**
     * 繰り返し指定されていなければ測定対象のメソッドは1回だけしか呼ばれない
     * @test
     */
    public function 繰り返し指定されていなければ測定対象のメソッドは1回だけしか呼ばれない()
    {
        // モック
        $mock = $this->getMockBuilder('mock')
            ->setMethods(array('test'))
            ->getMock()
        ;

        // 1回しか呼ばれない
        $mock
            ->expects($this->once())
            ->method('test')
        ;

        SMB\Benchmark::getInstance()
            ->measure(array($mock, 'test'), array(), 'bench1')
        ;
    }

    /**
     * 繰り返し指定回数が1以下または数値以外の場合測定対象のメソッドは1回だけしか呼ばれない
     * @test
     */
    public function 繰り返し指定回数が1以下または数値以外の場合測定対象のメソッドは1回だけしか呼ばれない()
    {
        // モック
        $mock = $this->getMockBuilder('mock')
            ->setMethods(array('test1', 'test2', 'test3', 'test4'))
            ->getMock()
        ;

        // 1回しか呼ばれないこと
        $mock->expects($this->once())->method('test1');
        $mock->expects($this->once())->method('test2');
        $mock->expects($this->once())->method('test3');
        $mock->expects($this->once())->method('test4');

        SMB\Benchmark::getInstance()
            ->measure(array($mock, 'test1'), array(), 'bench1', 1)
            ->measure(array($mock, 'test2'), array(), 'bench2', 0)
            ->measure(array($mock, 'test3'), array(), 'bench3', [])
            ->measure(array($mock, 'test4'), array(), 'bench4', 'hoge')
        ;
    }

    /**
     * 繰り返し指定されている時の測定結果はある程度平均値が出ているか
     * @test
     * @group hoge
     */
    public function 繰り返し指定されている時の測定結果はある程度平均値が出ているか()
    {
        $ret = SMB\Benchmark::getInstance()
                ->setScale(3) // 3桁に変更
                ->measure(function() {
                    usleep(2000); // 0.002秒
                }, array(), 'bench1', 100) // 100回繰り返す
                ;

        // 何回繰り返しても小数点3位までは一緒
        $this->assertSame('0.002', $ret->result('bench1'));
    }

}

/**
 * test用
 */
class Hoge
{
    public function callbackMethod()
    {
        usleep(10000);
    }

    public function callbackMethod2($arg)
    {
        usleep($arg);
    }

    public function callbackMethod3($arg, $arg2)
    {
        usleep($arg * $arg2);
    }

    public static function staticCallbackMethod($arg)
    {
        usleep($arg);
    }
}
