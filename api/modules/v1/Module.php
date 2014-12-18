<?php

namespace app\api\modules\v1;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\api\modules\v1\controllers';
    public $modelNamespace = 'app\api\modules\v1\models';

    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
