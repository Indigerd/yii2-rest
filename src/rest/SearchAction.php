<?php

namespace indigerd\rest;

use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\IndexAction as BaseIndexAction;

class SearchAction extends BaseIndexAction
{
    public $modelSearchClass;

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this);
        }
        if ($this->modelSearchClass !== null) {
            /* @var $modelClass \yii\db\ActiveRecord */
            $modelSearch = new $this->modelSearchClass;
            $dataProvider = $modelSearch->search(Yii::$app->getRequest()->getBodyParams());
        } else {
            /* @var $modelClass \yii\db\ActiveRecord */
            $modelClass = $this->modelClass;
            $dataProvider = new ActiveDataProvider([
                'query' => $modelClass::find(),
            ]);
        }
        return $dataProvider;
    }
}
