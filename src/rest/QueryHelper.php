<?php

namespace indigerd\rest;

use Yii;
use yii\helpers\ArrayHelper;

class QueryHelper
{
    public static $excludeField = ['fields', 'expand', 'sort', 'page', 'per-page', 'expand-fields', 'r', 'access_token'];

    public static  function createQuery($modelClass, $ignore=[])
    {
        $model  = $modelClass::find();
        $wheres = ['and'];
        $filterFields = self::getQueryParams($ignore);
        $conditionTransformFunctions = self::conditionTransformFunctions();
        foreach($filterFields as $key => $value) {
            if ($value == '' || in_array($key, self::$excludeField)) {
                continue;
            }
            $fieldKey = $key;
            if (!strpos($key, '.')) {
                $fieldKey =  $modelClass::tableName() . '.' . $key;
            } else {
                $relationModel = substr($fieldKey, 0, strrpos($key, '.'));
                $model->joinWith($relationModel);
                if (strpos($relationModel, '.')) {
                    $temp     = substr($fieldKey, strrpos($fieldKey, '.'));
                    $fieldKey = substr($relationModel, strrpos($relationModel, '.') + 1) . $temp;
                }
            }
            $type = 'EQUAL';
            if (preg_match("/^[A-Z]+_/", $value, $matches) && array_key_exists(
                    trim($matches[0], '_'),
                    $conditionTransformFunctions
            )) {
                $type  = trim($matches[0], '_');
                $value = str_replace($matches[0], '', $value);
            }
            $wheres = ArrayHelper::merge($wheres, [$conditionTransformFunctions[$type]($fieldKey, $value)]);
        }
        if (count($wheres) > 1) {
            $model->andWhere($wheres);
        }
        return $model;
    }

    public static function addOrderSort($sort, $table, $query, $pkFiled = 'id')
    {
        if ($sort == '') {
            $order = $table . '.' . $pkFiled . ' DESC';
        } else {
            $sorts = explode(',', $sort);
            foreach ($sorts as $sort) {
                if (!preg_match('/^[a-zA-Z0-9\._\s]+$/', $sort)) {
                    continue;
                }
                if (!strpos($sort,'.')) {
                    preg_match('/\w+\s+(DESC|ASC)/', $sort, $sortField);
                    $type  = !empty($sortField) ? trim($sortField[1]) : 'DESC';
                    $field = !empty($sortField) ? trim(substr($sort, 0, -strlen($type))) : trim($sort);
                    $order[$table . '.' . $field] = $type == 'DESC' ? SORT_DESC : SORT_ASC;
                } else {
                    $sortTable = trim(substr($sort, 0, strrpos($sort, '.')));
                    preg_match('/\w+\.\w+\s+(DESC|ASC)/', $sort, $sortField);
                    $type  = trim($sortField[1]);
                    $field = trim(substr(substr($sort, strrpos($sort, '.') + 1), 0, -strlen($type)));
                    $order[trim($sortTable) . '.' . $field] =  $type == 'DESC' ? SORT_DESC : SORT_ASC;;
                    $query->select[] = explode(' ', $sortField[0])[0];
                    $query->joinWith($sortTable);
                }
            }
            $query->select[] = $table . ".*";
        }
        $query->orderBy($order);
    }

    private static function getQueryParams($ignore)
    {
        $pairs = explode("&", urldecode(Yii::$app->getRequest()->queryString));
        $vars = [];
        foreach ($pairs as $pair) {
            if ($pair == '') {
                continue;
            }
            $nv = explode("=", $pair);
            if (count($nv) != 2) {
                continue;
            }
            $name  = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            if (!in_array($name, $ignore)) {
                $vars[$name] = $value;
            }
        }
        return $vars;
    }

    private static function splitParam($param)
    {
        $keys = explode(".", $param);
        $condition = '';
        $i = 1;
        foreach($keys as $key) {
            $condition .= '"' . $key . '"';
            if ($i < count($keys)) {
                $condition .= '.';
            }
            $i++;
        }
        return $condition;
    }

    public static function conditionTransformFunctions()
    {
        return [
            'EQUAL' => function($field, $value) {
                return [$field => $value];
            },
            'NOTEQUAL' => function($field, $value) {
                return ['NOT', [$field => $value]];
            },
            'NULL' => function($field, $value) {
                return [$field => null];
            },
            'LIKE' => function($field, $value) {
                return ['LIKE', $field, $value];
            },
            'LLIKE' => function($field, $value) {
                return ['LIKE', $field, '%' . $value, false];
            },
            'RLIKE' => function($field, $value) {
                return ['LIKE', $field, $value . '%', false];
            },
            'IN' => function($field, $value) {
                return ['IN', $field, explode(',', $value)];
            },
            'NOTIN' => function($field, $value) {
                return ['NOT IN', $field, explode(',', $value)];
            },
            'MIN' => function($field, $value) {
                return ['>=', preg_replace("/_min$/", '', $field, 1), $value];
            },
            'MAX' => function($field, $value) {
                $time = DateTimeHelper::isNormalTime($value);
                if (is_array($time)) {
                    $value = DateTimeHelper::getMaxNormalTime($time)['value'];
                    return ['<', preg_replace("/_max$/", '', $field, 1), $value];
                }
                return ['<=', preg_replace("/_max$/", '', $field, 1), $value];
            },
            'RANGE' => function($field, $value) {
                $time = DateTimeHelper::isNormalTime($value);
                if (is_array($time)) {
                    $maxTime = DateTimeHelper::getMaxNormalTime($time);
                    $value = DateTimeHelper::setNormalTime($time);
                    $maxValue = DateTimeHelper::setNormalTime($maxTime);
                    return [
                        'and',
                        "$field>='"
                        . date('Y-m-d H:i:s', strtotime($value))
                        . "' and $field<'" . date('Y-m-d H:i:s', strtotime($maxValue))
                        . "'"
                    ];
                }
            }
        ];
    }
}
