<?php

namespace indigerd\rest;

use Yii;
use yii\helpers\ArrayHelper;

class ActiveRecord  extends \yii\db\ActiveRecord
{
    public $expandParam = 'expand';

    public $expandFieldsParam = 'expand-fields';

    protected $specifyExtraFields = [];

    public function extraFields()
    {
        $fields         = [];
        $expand         = explode(',', Yii::$app->request->get($this->expandParam, ''));
        $expandFields   = explode(',', Yii::$app->request->get($this->expandFieldsParam, ''));
        $setExtraFields = function($expandField, $obj) use(&$setExtraFields) {
            if (count($expandField)<=0 || !$obj) {
                return;
            }
            $currentField = array_shift($expandField);
            if ($obj->getRelation($currentField, false) || array_key_exists($currentField, $obj->expandFields())) {
                $obj->specifyExtraFields[$currentField] = function($obj, $field) {
                    return $obj->$field;
                };
                if (is_array($obj->$currentField)) {
                    foreach($obj->$currentField as $item) {
                        $setExtraFields($expandField, $item);
                    }
                } else {
                    $setExtraFields($expandField, $obj->$currentField);
                }
                return;
            }
            return ;
        };
        foreach($expandFields as $key => $field) {
            $expandFields[$key] = explode('.', $field);
        }
        foreach($expand as $field) {
            $expandFields[] = [$field];
        }
        foreach($expandFields as $key => $field) {
            if($this->getRelation($field[0], false) || array_key_exists($field[0], $this->expandFields())) {
                $setExtraFields($field, $this);
                $fields[$field[0]] = function($obj, $field) {
                    return $obj->$field;
                };
            }
        }
        return ArrayHelper::merge($fields, $this->expandFields());
    }

    public function expandFields()
    {
        return [];
    }

    public function fields()
    {
        $fields = parent::fields();
        return ArrayHelper::merge($fields, $this->specifyExtraFields);
    }

    public function hasOne($class, $link, $alias = null)
    {
        $query = parent::hasOne($class, $link);
        if ($alias == null) {
            $alias = lcfirst(str_replace('_', '', $class::tableName()));
        }
        return $query->from([$alias => $class::tableName()]);
    }

    public function hasMany($class, $link, $alias = null)
    {
        $query = parent::hasMany($class, $link);
        if ($alias == null) {
            $alias = lcfirst(str_replace('_', '', $class::tableName()));
        }
        return $query->from([$alias => $class::tableName()]);
    }
}
