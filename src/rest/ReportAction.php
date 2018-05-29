<?php

namespace indigerd\rest;

use Psr\Http\Message\RequestInterface;
use yii\rest\Action;
use yii\web\Request;

class ReportAction extends Action
{
    public $propertiesModelClass;

    public function run()
    {
        $properties = [];
        $model = new $this->modelClass;
        foreach ($model->attributes() as $field) {
            $property = new $this->propertiesModelClass;
            $property->name = $field;
            $property->type = $this->getFieldType($model, $field);
            $property->required = $this->getFieldRequired($model, $field);
            $properties[] = $property;
        }
        return $properties;
    }

    protected function defaultReport(Request $request)
    {

    }
}
