<?php

namespace indigerd\rest;

use Yii;
use yii\data\ActiveDataProvider;

class IndexAdvancedSearchAction extends \yii\rest\IndexAction
{
    protected function prepareDataProvider()
    {
        /* @var $modelClass \yii\db\ActiveRecord */
        $modelClass = $this->modelClass;
        $query = QueryHelper::createQuery($this->modelClass);
        QueryHelper::addOrderSort(Yii::$app->request->get('sort',''), $modelClass::tableName(), $query);
        $pagination = false;
        if (Yii::$app->request->get('per-page') == '0') {
            $pagination = null;
        }
        if ((int)Yii::$app->request->get('per-page') > 0) {
            $pagination = (int)Yii::$app->request->get('per-page');
        }
        return new ActiveDataProvider([
            'query'      => $query->distinct(),
            'pagination' => $pagination
        ]);
    }
}
