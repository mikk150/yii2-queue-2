<?php

namespace tests\unit\cli;

use Codeception\Stub\Expected;
use tests\TestCase;
use yii\queue\cli\LoadWatcher;

class LoadWatcherTest extends TestCase
{
    public function testShouldGetJobWhenLoadIsHigh()
    {
        $loadWatcher = $this->construct(LoadWatcher::class, [[
            'maxCpuLoad' => 80
            ]], [
            'getLinuxPercentage' => function () {
                return 90;
            }
            ]
        );

        $this->assertFalse($loadWatcher->shouldGetJob());
    }

    public function testShouldGetJobWhenLoadIs100()
    {
        $loadWatcher = new LoadWatcher([
            'maxCpuLoad' => 80,
            'statFile' => codecept_output_dir() . 'fakestat'
        ]);

        file_put_contents(codecept_output_dir() . 'fakestat', <<<FAKESTAT
cpu  2 1 1 4 0 0 0 0 0
cpu0 2 1 1 4 0 0 0 0 0
cpu1 2 1 1 4 0 0 0 0 0
FAKESTAT
        );

        $loadWatcher->shouldGetJob(); //take first measurement

        usleep(LoadWatcher::CHECK_INTERVAL * 1000000 * 2);
        file_put_contents(codecept_output_dir() . 'fakestat', <<<FAKESTAT
cpu  3 1 1 4 0 0 0 0 0
cpu0 3 1 1 4 0 0 0 0 0
cpu1 3 1 1 4 0 0 0 0 0
FAKESTAT
        );

        $this->assertFalse($loadWatcher->shouldGetJob());
    }

    public function testShouldGetJobWhenJiffiesNotFound()
    {
        $loadWatcher = new LoadWatcher([
            'maxCpuLoad' => 80,
            'statFile' => codecept_output_dir() . 'fakestat'
        ]);

        file_put_contents(codecept_output_dir() . 'fakestat', <<<FAKESTAT
not jiffies
FAKESTAT
        );

        $this->assertTrue($loadWatcher->shouldGetJob()); //take first measurement
    }

    function testCheckingJiffiesTooQuickly()
    {
        $loadWatcher = $this->construct(LoadWatcher::class, [[
            'maxCpuLoad' => 80
            ]], [
                'getLinuxPercentage' => Expected::once(90)
            ]
        );

        $this->assertFalse($loadWatcher->shouldGetJob());
        $this->assertFalse($loadWatcher->shouldGetJob());
    }
}
