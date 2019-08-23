<?php

namespace yii\queue\cli;

use yii\base\BaseObject;

class LoadWatcher extends BaseObject
{
    const LINUX_JIFFIES = '/^cpu[\s]+(?<user>[\d]+)[\s]+(?<nice>[\d]+)[\s]+(?<system>[\d]+)[\s]+(?<idle>[\d]+)/';
    const CHECK_INTERVAL = 0.1;
    
    protected $percentage = 0;

    private $_previousLinuxJiffies = ['user' => 1, 'nice' => 1, 'system' => 1, 'idle' => 1];
    private $_lastCheck = 0;

    public function getPercentage()
    {
        if ($this->_lastCheck > microtime(true) - self::CHECK_INTERVAL) {
            return $this->percentage;
        }

        if (stristr(PHP_OS, "win")) {

        }
        return $this->percentage = $this->getLinuxPercentage();
    }

    protected function getWindowsPercentage()
    {
        $cmd = 'wmic cpu get loadpercentage /all';
        @exec($cmd, $output);

        if ($output) {
            foreach ($output as $line) {
                if ($line && preg_match('/^[0-9]+\$/', $line)) {
                    $load = $line;
                    break;
                }
            }
        }
    }

    protected function getLinuxPercentage()
    {
        if ($jiffies = $this->getLinuxJiffies()) {
            $freeTime = [
                'user' => $jiffies['user']     - $this->_previousLinuxJiffies['user'],
                'nice' => $jiffies['nice']     - $this->_previousLinuxJiffies['nice'],
                'system' => $jiffies['system'] - $this->_previousLinuxJiffies['system'],
                'idle' => $jiffies['idle']     - $this->_previousLinuxJiffies['idle'],
            ]; //compute used time on CPU per work
            $this->_previousLinuxJiffies = $jiffies;

            $cpuTime = array_sum($freeTime); //compute total time on CPU

            $this->_lastCheck = microtime(true);
            return $this->percentage = (100 - ($freeTime['idle'] * 100 / $cpuTime)); // compute 
        }

        return 0; //if no jiffies line were found - first of all why? assume load is 0
    }

    /**
     * Finds total CPU linux stat line and returns it parsed
     * 
     * @return array|null
     */
    private function getLinuxJiffies()
    {
        $stats = @file_get_contents("/proc/stat");
        $stats = explode("\n", $stats);

        // Separate values and find line for overall CPUs jiffies
        foreach ($stats as $statLine) {
            if (preg_match(self::LINUX_JIFFIES, $statLine, $matches)) {
                return $matches;
            }
        }
    }
}
