<?php
namespace indigerd\rest;

use Yii;
use yii\data\ActiveDataProvider;

class DictionaryController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['access']);
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'index' => [
                'class' => 'yii\rest\IndexAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'prepareDataProvider' => function ($action) {
                    /** @var \yii\db\ActiveRecord $model */
                    $model = $this->modelClass;
                    $dataProvider = new ActiveDataProvider([
                        'query' => $model::find(),
                        'pagination' => false
                    ]);

                    return $model::getDb()->cache(
                        function () use ($dataProvider) {
                            $dataProvider->prepare();
                            return $dataProvider;
                        },
                        $model::getDb()->queryCacheDuration
                    );
                }
            ],
            'options' => [
                'class' => 'yii\rest\OptionsAction',
                'resourceOptions' => ['GET', 'HEAD', 'OPTIONS']
            ],
        ];
    }
}
