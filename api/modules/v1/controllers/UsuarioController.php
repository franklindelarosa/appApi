<?php

namespace app\api\modules\v1\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\rest\Controller;
use app\api\modules\v1\models\Usuario;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class UsuarioController extends Controller
{
	public function behaviors()
    {
        // $behaviors = parent::behaviors();
        // $behaviors['authenticator'] = [
        //     'class' => HttpBasicAuth::className(),
        // ];
        // return $behaviors;
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'crear' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                // 'only' => ['index', 'logout'],
                'rules' => [
                    [
                        'allow' => false,
                        // 'actions' => ['index'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        // 'actions' => ['index', 'logout'],
                        'roles' => ['Administrador'],
                    ],
                ],
            ],
        ];
    }

    public function actionListar()
    {
        \Yii::$app->response->format = 'json';
        return Usuario::find()->all();
    }

    public function actionIndex(){
    	return $this->render('index');
    }

    /**
    * Creates a new User model.
    * @return json
    */
    public function actionCrear()
    {
    	\Yii::$app->response->format = 'json';
        if (Yii::$app->request->post()) {
            // return ['mensaje' => $_REQUEST];
            $model = new Usuario();
            $model->nombre = $_POST['nombre'];
            $model->correo = $_POST['correo'];
            $model->usuario = $_POST['correo'];
            $model->contrasena = sha1($_POST['contrasena']);
            $model->sexo = $_POST['sexo'];
            $model->telefono = $_POST['telefono'];
            // $model->contrasena = sha1($model->contrasena);
            // if($model->perfil === '' || $model->perfil === NULL){
                $model->perfil = 'Jugador';
                // $model->estado = '1';
            // return $model->attributes;
            // }
            if($model->save()){
                // $role = Yii::$app->authManager->getRole($model->perfil);
                // Yii::$app->authManager->assign($role, $model->id_usuario);
                return ['respuesta' => '1', 'mensaje' => 'Guardado correctamente'];
            }else{
                return ['respuesta' => '2', 'mensaje' => 'No guardó'];
            }
        } else {
            return ['respuesta' => '0', 'mensaje' => 'No se pudo guardar'];
        }
    }
}
