<?php

namespace tests\stubs;

use yii\queue\cli\LoadWatcher;

class DummyLoadWatcher extends LoadWatcher
{
    public function shouldGetJob()
    {
        return true;
    }
}
