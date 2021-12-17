<?php
namespace indigerd\rest;

use Yii;
use yii\filters\Cors;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use indigerd\oauth2\authfilter\filter\AuthFilter;

class Controller extends ActiveController
{
    public $propertiesModelClass = 'indigerd\rest\Property';

    /**
     * @var string
     */
    public $modelSearchClass;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::className(),
                'cors'  => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => [
                        'GET',
                        'POST',
                        'PUT',
                        'PATCH',
                        'DELETE',
                        'LINK',
                        'UNLINK',
                        'LOCK',
                        'UNLOCK',
                        'HEAD',
                        'OPTIONS',
                        'PROPFIND',
                        'SEARCH',
                        'PURGE',
                        'COPY',
                        'MOVE',
                        'VIEW',
                        'REPORT',
                    ],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => null,
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [
                        'X-Pagination-Current-Page',
                        'X-Pagination-Page-Count',
                        'X-Pagination-Per-Page',
                        'X-Pagination-Total-Count'
                    ],
                ]
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'application/xml'  => Response::FORMAT_XML,
                ],
            ],
            'verbFilter' => [
                'class'   => VerbFilter::className(),
                'actions' => $this->verbs(),
            ],
            'access' => [
                'class' => AuthFilter::className(),
                'except' => [
                    'options',
                    'properties'
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['properties'] = ['PROPFIND'];
        $verbs['search'] = ['SEARCH'];
        return $verbs;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return array_merge(
            parent::actions(),
            [
                'properties' => [
                    'class' => 'indigerd\rest\PropertiesAction',
                    'modelClass' => $this->modelClass,
                    'propertiesModelClass' => $this->propertiesModelClass,
                ],
                'search' => [
                    'class' => 'indigerd\rest\SearchAction',
                    'modelSearchClass' => $this->modelSearchClass,
                ],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        try {
            $result = parent::runAction($id, $params);
        } catch (\yii\web\HttpException $e) {
            if ($e->getCode() > 0) {
                return $this->actionException($e);
            }
            $eClass = get_class($e);
            if ($eClass == 'yii\web\HttpException') {
                $exception = new $eClass($e->statusCode, $e->getMessage(), $e->statusCode);
            } else {
                $exception = new $eClass($e->getMessage(), $e->statusCode);
            }
            return $this->actionException($exception);
        } catch (\yii\base\InvalidRouteException $e) {
            return $this->actionException(new NotFoundHttpException('Invalid route'));
        } catch (\Exception $e) {
            return $this->actionException($e);
        }
        return $result;
    }

    /**
     * @param \Exception $e
     * @return array
     */
    protected function actionException(\Exception $e)
    {
        if (Yii::$app->response->format == Response::FORMAT_HTML) {
            $this->negotiate();
        }
        defined('YII_DEBUG') or define('YII_DEBUG', false);
        $name    = $e instanceof \yii\web\HttpException ? $e->getName() : Response::$httpStatuses[500];
        $status  = $e instanceof \yii\web\HttpException ? $e->statusCode : 500;
        $message = $e instanceof \yii\web\HttpException ? $e->getMessage() : (YII_DEBUG ? $e->getMessage() : '');
        Yii::$app->response->setStatusCode($status);
        $result = [
            'name'    => $name,
            'message' => $message,
            'code'    => $e->getCode(),
            'status'  => $status
        ];
        if (YII_DEBUG) {
            $result['stackTrace'] = $e->getTraceAsString();
        }
        return $result;
    }
}
