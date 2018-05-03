<?php

namespace indigerd\rest;

use Yii;
use yii\rest\UrlRule as BaseUrlRule;
use yii\web\UrlRuleInterface;
use yii\web\UrlRule as WebUrlRule;

class UrlRule extends BaseUrlRule
{

    protected $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS|LINK|UNLINK|LOCK|UNLOCK|PURGE|COPY|MOVE|PROPFIND|VIEW|SEARCH|REPORT';

    public $patterns = [
        'PUT,PATCH {id}' => 'update',
        'DELETE {id}' => 'delete',
        'GET,HEAD {id}' => 'view',
        'POST' => 'create',
        'GET,HEAD' => 'index',
        'PROPFIND' => 'properties',
        '{id}' => 'options',
        '' => 'options',
    ];

    /**
     * Creates a URL rule using the given pattern and action.
     *
     * @param string $pattern
     * @param string $prefix
     * @param string $action
     *
     * @return UrlRuleInterface
     */
    protected function createRule($pattern, $prefix, $action)
    {

        $verbs = $this->verbs;
        if (preg_match("/^((?:($verbs),)*($verbs))(?:\\s+(.*))?$/", $pattern, $matches)) {
            $verbs = explode(',', $matches[1]);
            $pattern = isset($matches[4]) ? $matches[4] : '';
        } else {
            $verbs = [];
        }
        $config = $this->ruleConfig;
        $config['verb'] = $verbs;
        $config['pattern'] = rtrim($prefix . '/' . strtr($pattern, $this->tokens), '/');
        $config['route'] = $action;
        if (!empty($verbs) && !in_array('GET', $verbs)) {
            $config['mode'] = WebUrlRule::PARSING_ONLY;
        }
        $config['suffix'] = $this->suffix;
        return Yii::createObject($config);
    }
}
