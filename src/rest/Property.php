<?php

namespace indigerd\rest;

use yii\base\Model;

class Property extends Model
{
    public $name;
    public $required = false;
    public $type = 'string';
}
