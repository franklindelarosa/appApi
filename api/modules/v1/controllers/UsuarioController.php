<?php

namespace app\api\modules\v1\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use app\api\modules\v1\models\Usuario;
use app\api\modules\v1\models\Canchas;
use app\api\modules\v1\models\Partidos;
use app\api\modules\v1\models\Consulta;
use app\api\modules\v1\models\Estados;
use app\api\modules\v1\models\Invitados;

class UsuarioController extends Controller
{
	public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
        ];
        return $behaviors;
        // $behaviors = parent::behaviors();
        // $behaviors['authenticator'] = [
        //     'class' => CompositeAuth::className(),
        //     'authMethods' => [
        //         HttpBasicAuth::className(),
        //         HttpBearerAuth::className(),
        //         QueryParamAuth::className(),
        //     ],
        // ];
        // return $behaviors;
        // return [
        //     'verbs' => [
        //         'class' => VerbFilter::className(),
        //         'actions' => [
        //             'crear' => ['post'],
        //         ],
        //     ],
        //     'access' => [
        //         'class' => AccessControl::className(),
        //         // 'only' => ['index', 'logout'],
        //         'rules' => [
        //             [
        //                 'allow' => false,
        //                 // 'actions' => ['index'],
        //                 'roles' => ['?'],
        //             ],
        //             [
        //                 'allow' => true,
        //                 // 'actions' => ['index', 'logout'],
        //                 'roles' => ['Administrador'],
        //             ],
        //         ],
        //     ],
        // ];
    }

    public function actionListar()
    {
        \Yii::$app->response->format = 'json';
        return Usuario::find()->all();
    }

    public function actionListarCanchas()
    {
        \Yii::$app->response->format = 'jsonp';
        return Canchas::find()->all();
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
    	// \Yii::$app->response->format = 'json';
        if(Yii::$app->user->can('Administrador')){
            return ['mensaje' => 'Eres Administrador'];
        }else{
            return ['mensaje' => 'Tú no eres Administrador, eres jugador'];
        }
        if (Yii::$app->request->post()) {
            // return ['mensaje' => $_REQUEST];
            $model = new Usuario();
            $model->nombre = $_POST['nombre'];
            $model->correo = $_POST['correo'];
            $model->usuario = $_POST['correo'];
            $model->contrasena = sha1($_POST['contrasena']);
            $model->accessToken = md5($_POST['contrasena']);
            $model->authKey = md5($_POST['contrasena']);
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
                return ['respuesta' => '1', 'mensaje' => 'Guardado correctamente'/*, 'auth' => Yii::$app->user->identity*/];
            }else{
                return ['respuesta' => '2', 'mensaje' => 'No guardó'/*, 'auth' => Yii::$app->user->identity*/];
            }
        } else {
            return ['respuesta' => '0', 'mensaje' => 'No se pudo guardar'];
        }
    }
}
