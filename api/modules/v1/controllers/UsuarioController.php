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
                'quien-soy' => ['post'],
                'sacar-jugador' => ['post'],
                'registrar-invitado' => ['post'],
                'actualizar-perfil' => ['post'],
                'info-perfil' => ['post'],
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

    //Esta acción le permite al cliente conocer el id de quién está logueado
    public function actionQuienSoy(){
        \Yii::$app->response->format = 'json';
        return ['id' => Yii::$app->user->id];
    }

    //Esta acción elimina a un jugador de un partido, recibe el partido, el equipo, el id del jugador a sacar
    //y la entidad del jugador a sacar (usuarios|invitado)
    public function actionSacarJugador(){
    $transaction = \Yii::$app->db->beginTransaction();
        try {
            if($_POST['entidad'] === 'usuario'){
                $sql = "DELETE FROM usuarios_partidos WHERE id_partido = ".$_POST['partido']." AND id_usuario = ".Yii::$app->user->id;
                \Yii::$app->db->createCommand($sql)->execute();
                $sql = "SELECT id_invitado, equipo FROM invitaciones WHERE id_partido = ".$_POST['partido']." AND id_usuario = ".Yii::$app->user->id;
                $result['invitados'] = \Yii::$app->db->createCommand($sql)->queryAll();
                $sql = "DELETE FROM invitaciones WHERE id_partido = ".$_POST['partido']." AND id_usuario = ".Yii::$app->user->id;
                \Yii::$app->db->createCommand($sql)->execute();
                $sql = "UPDATE partidos SET ".$_POST['equipo']." = (".$_POST['equipo']."-1) WHERE id_partido = ".$_POST['partido'];
                \Yii::$app->db->createCommand($sql)->execute();
                $result['yo'] = \Yii::$app->user->id;
            }else{
                $sql = "DELETE FROM invitaciones WHERE id_partido = ".$_POST['partido']." AND id_invitado = ".$_POST['jugador'];
                \Yii::$app->db->createCommand($sql)->execute();
                $sql = "DELETE FROM invitados WHERE id_invitado = ".$_POST['jugador'];
                \Yii::$app->db->createCommand($sql)->execute();
            }
            $transaction->commit();
            $result['status'] = 'ok';
            $result['equipo'] = $_POST['equipo'];
        } catch (Exception $e) {
            $result['status'] = 'bad';
            $result['mensaje'] = $e->getMessage();
            $transaction->rollBack();
        }
        \Yii::$app->response->format = 'json';
        return $result;
    }

    //Esta acción añade el usuario al partido especificado
    public function actionRegistrarUsuario(){
        \Yii::$app->response->format = 'json';
        $transaction = \Yii::$app->db->beginTransaction();
        $user = Usuario::findOne(Yii::$app->user->id);
        try {
            $sql = "SELECT COUNT(*) FROM usuarios_partidos WHERE id_usuario = ".Yii::$app->user->id." AND id_partido = ".$_POST['partido'];
            $conteo = \Yii::$app->db->createCommand($sql)->queryScalar();
            if($conteo < 1){
                $sql = "SELECT fecha, hora FROM partidos WHERE id_partido = ".$_POST['partido'];
                $tiempo = \Yii::$app->db->createCommand($sql)->queryAll();
                $sql = "SELECT COUNT(*), c.nombre FROM usuarios_partidos ut, partidos p, canchas c WHERE ut.id_usuario = ".
                \Yii::$app->user->id." AND ut.id_partido <> ".$_POST['partido']." AND p.id_partido = ut.id_partido AND p.fecha = '".
                $tiempo[0]['fecha']."' AND p.hora = '".$tiempo[0]['hora']."' AND p.id_cancha = c.id_cancha";
                $query = \Yii::$app->db->createCommand($sql);
                $conteo = $query->queryScalar();
                $cancha = $query->queryAll();
                // return ['result' => $cancha];
                // return [$tiempo[0]['fecha'], $tiempo[0]['hora'], $conteo, $cancha];
                if($conteo < 1){
                    $sql = "INSERT INTO usuarios_partidos (id_usuario, id_partido, equipo) VALUES (".Yii::$app->user->id.", ".$_POST['partido'].", '".substr($_POST['equipo'],0,1)."')";
                    \Yii::$app->db->createCommand($sql)->execute();
                    $sql = "UPDATE partidos SET ".$_POST['equipo']." = (".$_POST['equipo']."+1) WHERE id_partido = ".$_POST['partido'];
                    \Yii::$app->db->createCommand($sql)->execute();
                    $result['entidad'] = 'usuario';
                    $result['equipo'] = substr($_POST['equipo'],0,1);
                    $result['id'] = Yii::$app->user->id;
                    $result['nombre'] = $user->nombres." ".$user->apellidos;
                    $status = 'ok';
                    $mensaje = 'Todo bien';
                }else{
                    $status = 'bad';
                    $result['tipo'] = 1;
                    $mensaje = "El usuario va a jugar otro partido el mismo día a la misma hora en la cancha ".$cancha[0]['nombre'];
                }
            }else{
                $status = 'bad';
                $result['tipo'] = 2;
                $mensaje = "El usuario ya existe en el partido";
            }
            $transaction->commit();
        } catch (Exception $e) {
            $status = 'bad';
            $result['tipo'] = 3;
            $mensaje = "Hubo un error en la transacción, inténtalo en breve";
            $transaction->rollBack();
        }
        \Yii::$app->response->format = 'json';
        return ['status' => $status, 'data' => $result, 'mensaje' => $mensaje];
    }

    //Esta acción recibe el id del partido, el equipo (blancos/negros) y los datos del invitado para registrarlo en el partido
    public function actionRegistrarInvitado(){
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $invitado = new Invitados();
            $invitado->nombres = $_POST['nombres'];
            $invitado->apellidos = $_POST['apellidos'];
            $invitado->correo = $_POST['correo'];
            $invitado->sexo = $_POST['sexo'];
            $invitado->telefono = $_POST['telefono'];
            if($invitado->save()){
                $sql = "INSERT INTO invitaciones (id_usuario, id_invitado, equipo, id_partido) VALUES ('".Yii::$app->user->id."', '".$invitado->id_invitado."', '".substr($_POST['equipo'],0,1)."', '".$_POST['partido']."')";
                \Yii::$app->db->createCommand($sql)->execute();
                $sql = "UPDATE partidos SET ".$_POST['equipo']." = (".$_POST['equipo']."+1) WHERE id_partido = ".$_POST['partido'];
                \Yii::$app->db->createCommand($sql)->execute();
                $status = 'ok';
                $result['id'] = $invitado->id_invitado;
                $result['nombre'] = $_POST['nombres']." ".$_POST['apellidos'];
                $result['equipo'] = $_POST['equipo'];
                $result['responsable'] = Yii::$app->user->id;
                $transaction->commit();
            }
        } catch (Exception $e) {
            $status = 'bad';
            $transaction->rollBack();
        }
        \Yii::$app->response->format = 'json';
        return ['status' => $status, 'data' => $result];
    }

    //Esta acción permite actualizar el perfil de un jugador, devuelve status = 'ok' si se pudo guardar, si no se pudo status = 'bad'
    public function actionActualizarPerfil()
    {
        $model = $this->findModel(Yii::$app->user->id);
        $contrasena = $model->contrasena;
        $model->nombres = $_POST['nombres'];
        $model->apellidos = $_POST['apellidos'];
        $model->correo = $_POST['correo'];
        ($_POST['contrasena'] === '') ? $model->contrasena = $contrasena : $model->contrasena = sha1($_POST['contrasena']);
        $model->usuario = $_POST['correo'];
        $model->telefono = $_POST['telefono'];
        $model->sexo = $_POST['sexo'];
        $model->accessToken = md5($model->contrasena);
        // $model->perfil = 'Jugador';
        if($model->save()){
            return ['status' => 'ok', 'mensaje' => 'Actualizado correctamente', 'key' => $model->accessToken];
        }else{
            return ['status' => 'bad', 'mensaje' => 'No se pudo actualizar'/*, 'auth' => Yii::$app->user->identity*/];
        }
    }

    //Devuelve la información de un perfil con el último partido jugado
    public function actionInfoPerfil(){
        \Yii::$app->response->format = 'json';
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $sql = "SELECT CONCAT(nombres, ' ', apellidos) nombre, correo, (if(sexo = 'f','Femenino','Masculino')) sexo, telefono FROM usuarios WHERE id_usuario = ".Yii::$app->user->id;
            $user = \Yii::$app->db->createCommand($sql)->queryOne();
            $result['data'] = $user;
            $sql = "SET lc_time_names = 'es_CO'";
            Yii::$app->db->createCommand($sql)->execute();
            $sql = "SELECT p.fecha, DATE_FORMAT(p.fecha, '%W %e %M') label_fecha, p.hora, DATE_FORMAT(p.hora, '%h:%i %p') label_hora, c.* FROM usuarios_partidos ut, partidos p, canchas c WHERE ut.id_usuario = ".
            Yii::$app->user->id." AND p.estado = 2 AND ut.id_partido = p.id_partido AND p.id_cancha = c.id_cancha ORDER BY p.fecha DESC, p.hora DESC LIMIT 0,1";
            $last = \Yii::$app->db->createCommand($sql)->queryOne();
            $result['ultimo_partido'] = $last;
            $sql = "SELECT COUNT(*) FROM usuarios_partidos ut, partidos p WHERE ut.id_usuario = ".Yii::$app->user->id." AND ut.id_partido = p.id_partido AND p.estado = 2";
            $totalPartidos = \Yii::$app->db->createCommand($sql)->queryScalar();
            // return $totalPartidos;
            $result['total'] = $totalPartidos;
            $transaction->commit();
            $result['status'] = 'ok';
        } catch (Exception $e) {
            $result['status'] = 'bad';
            $transaction->rollBack();
        }
        return $result;
    }

    //Esta función busca a un usuario por la primary key ($id)
    protected function findModel($id)
    {
        if (($model = Usuario::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
