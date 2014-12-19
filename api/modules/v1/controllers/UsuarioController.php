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
use yii\db\Query;

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
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'listar-canchas' => ['post'],
            ],
        ];
        // $behaviors['access'] = [
        //     'class' => AccessControl::className(),
        //     // 'only' => ['index', 'logout'],
        //     'rules' => [
        //         [
        //             'allow' => false,
        //             // 'actions' => ['index'],
        //             'roles' => ['?'],
        //         ],
        //         [
        //             'allow' => true,
        //             // 'actions' => ['index', 'logout'],
        //             'roles' => ['Administrador'],
        //         ],
        //     ],
        // ];
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
    }

    public function actionListar()
    {
        \Yii::$app->response->format = 'json';
        return Usuario::find()->all();
    }

    //Regresa las canchas que tienen partidos por jugar (disponibles) ordenadas ascendentemente por el nombre
    public function actionListarCanchas()
    {
        \Yii::$app->response->format = 'json';
        // Resultado por queryBuilder:
        $query = new Query;
        $query->select('c.*')->distinct()->from("canchas c")->innerJoin("partidos p", "p.id_cancha = c.id_cancha AND p.estado = :estado");
        return $query->addParams([':estado' => Partidos::STATUS_DISPONIBLE])->orderBy(['c.nombre' => SORT_ASC])->all();

        // Resultado por Data Access Object:
        // $sql = "SELECT DISTINCT c.* FROM canchas c, partidos p WHERE c.id_cancha = p.id_cancha AND p.estado = :estado";
        // return Yii::$app->db->createCommand($sql)->bindValue(':estado', Partidos::STATUS_DISPONIBLE)->queryAll();

        // Resultado por ActiveRecords:
        // return Canchas::find()->innerJoinWith([
        //         'partidos' => function ($query){
        //             $query->where('partidos.estado = :estado')->addParams([':estado' => Partidos::STATUS_DISPONIBLE]);
        //         }
        //     ])->all();
    }

    //Recibe como parámetro el id de una cancha y lista los días de los partidos por jugar (disponibles)
    //de esa cancha del mas cercano al mas lejano
    public function actionCanchaDias(){
        \Yii::$app->response->format = 'json';
        //SELECT @@lc_time_names;
        $sql = "SET lc_time_names = 'es_CO'";
        Yii::$app->db->createCommand($sql)->execute();
        $sql = "SELECT fecha, DATE_FORMAT(fecha, '%W %e %M') label FROM partidos WHERE estado = :estado AND id_cancha = :id_cancha ORDER BY fecha ASC ";
        return Yii::$app->db->createCommand($sql)->bindValue(':estado', Partidos::STATUS_DISPONIBLE)
        ->bindValue(':id_cancha', $_POST['cancha'])->queryAll();
    }

    //Recibe como parámetro el id de una cancha y la fecha para listar las horas de los partidos por jugar (disponibles)
    //de esa cancha del partido mas cercano al mas lejano, total jugadores (blancos y negros) y cupo máximo de la cancha
    public function actionCanchaHoras(){
        \Yii::$app->response->format = 'json';
        //SELECT @@lc_time_names;
        $sql = "SET lc_time_names = 'es_CO'";
        Yii::$app->db->createCommand($sql)->execute();
        $sql = "SELECT hora, DATE_FORMAT(hora, '%r') label, blancos, negros, (blancos+negros) total FROM partidos WHERE estado = :estado AND id_cancha = :id_cancha AND fecha = :fecha ORDER BY hora ASC";
        $result = Yii::$app->db->createCommand($sql)->bindValue(':estado', Partidos::STATUS_DISPONIBLE)
        ->bindValue(':id_cancha', $_POST['cancha'])
        ->bindValue(':fecha', $_POST['fecha'])->queryAll();
        $sql = "SELECT cupo_max FROM canchas WHERE id_cancha = :id_cancha";
        $cupo = Yii::$app->db->createCommand($sql)->bindValue(':id_cancha', $_POST['cancha'])->queryOne();
        return ['cupo' => $cupo, 'result' => $result];
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
