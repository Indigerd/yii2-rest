<?php

namespace indigerd\rest;

use yii\rest\Action;

class PropertiesAction extends Action
{
    public $propertiesModelClass;

    public function run()
    {
        $properties = [];
        $model = new $this->modelClass;
        foreach ($model->fields() as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            $property = new Property();
            $property->name = $field;
            $property->type = $this->getFieldType($model, $field);
            $property->required = $this->getFieldRequired($model, $field);
            $properties[] = $property;
        }
        return $properties;
    }

    protected function getFieldType($model, $field)
    {
        $type = 'string';
        $supportedTypes = [
            'boolean',
            'email',
            'date',
            'double',
            'ip',
            'integer',
            'number',
            'string',
            'url'
        ];
        foreach ($model->rules as $rule) {
            $fields = $rule[0];
            if (!is_array($fields)) {
                $fields = [$fields];
            }
            if (in_array($rule[1], $supportedTypes) and in_array($field, $fields)) {
                return $rule[1];
            }
        }
        return $type;
    }

    protected function getFieldRequired($model, $field)
    {
        $required = false;
        foreach ($model->rules as $rule) {
            $fields = $rule[0];
            if (!is_array($fields)) {
                $fields = [$fields];
            }
            if ($rule[1] == 'required' and in_array($field, $fields)) {
                return true;
            }
        }
        return $required;
    }
}
