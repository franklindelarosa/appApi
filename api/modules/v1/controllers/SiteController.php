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

class SiteController extends Controller
{
	public function behaviors()
    {
        $behaviors = parent::behaviors();
        // $behaviors['authenticator'] = [
        //     'class' => QueryParamAuth::className(),
        // ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'listar-canchas' => ['post'],
                'canchas-dias' => ['post'],
                'canchas-horas' => ['post'],
                'equipos' => ['post'],
                'login' => ['post'],
                'registrar-perfil' => ['post'],
            ],
        ];
        return $behaviors;
    }

    // public function actionListar()
    // {
    //     \Yii::$app->response->format = 'json';
    //     return ['status' => 'ok', 'data' => Usuario::find()->all()];
    // }

    //Regresa las canchas que tienen partidos por jugar (disponibles) ordenadas ascendentemente por el nombre
    public function actionListarCanchas()
    {
        \Yii::$app->response->format = 'json';
        // Resultado por queryBuilder:
        $query = new Query;
        $query->select('c.*')->distinct()->from("canchas c")->innerJoin("partidos p", "p.id_cancha = c.id_cancha AND p.estado = :estado");
        return ['status' => 'ok', 'data' => $query->addParams([':estado' => Partidos::STATUS_DISPONIBLE])->orderBy(['c.nombre' => SORT_ASC])->all()];

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
        return ['status' => 'ok', 'data' => Yii::$app->db->createCommand($sql)->bindValue(':estado', Partidos::STATUS_DISPONIBLE)
                ->bindValue(':id_cancha', $_POST['cancha'])->queryAll()];
    }

    //Recibe como parámetro el id de una cancha y la fecha, para listar las horas de los partidos por jugar (disponibles)
    //de esa cancha, del partido mas cercano al mas lejano, total jugadores (blancos y negros) y el cupo máximo de la cancha
    //en la primera posición del JSON
    //
    //Estructura de la Respuesta: [{status, data[{}], cupo_max}]
    public function actionCanchaHoras(){
        \Yii::$app->response->format = 'json';
        //SELECT @@lc_time_names;
        $transaction = \Yii::$app->db->beginTransaction();
        // $cupo = 'bad';
        try {
            $sql = "SET lc_time_names = 'es_CO'";
            Yii::$app->db->createCommand($sql)->execute();
            $sql = "SELECT hora, DATE_FORMAT(hora, '%r') label, blancos, negros, (blancos+negros) total FROM partidos WHERE estado = :estado AND id_cancha = :id_cancha AND fecha = :fecha ORDER BY hora ASC";
            $result = Yii::$app->db->createCommand($sql)->bindValue(':estado', Partidos::STATUS_DISPONIBLE)
            ->bindValue(':id_cancha', $_POST['cancha'])
            ->bindValue(':fecha', $_POST['fecha'])->queryAll();
            // $sql = "SELECT cupo_max cupo_maximo FROM canchas WHERE id_cancha = :id_cancha";
            // $cupo = Yii::$app->db->createCommand($sql)->bindValue(':id_cancha', $_POST['cancha'])->queryOne();
            $response['status'] = 'ok'; $response['data'] = $result;
        } catch (Exception $e) {
            $response['status'] = 'bad';
            $transaction->rollBack();
        }
        // return [$response, $cupo];
        return $response;
    }

    protected function buscarPartidoPorTiempo($cancha, $fecha, $hora){
        $sql = "SELECT id_partido FROM partidos WHERE id_cancha = :id_cancha AND fecha = :fecha AND hora = :hora";
        return Yii::$app->db->createCommand($sql)
        ->bindValue(':id_cancha', $cancha)
        ->bindValue(':fecha', $fecha)
        ->bindValue(':hora', $hora)->queryScalar();
    }

    //Esta acción invoca a la función "buscarPartidoPorTiempo" para que le devuelva el id del partido y pueda regresar
    //el listado de los jugadores (invitados y registrados) de ese partido.
    //
    //Estructura de la Respuesta:
    //result[0] = blancos, result[1] = negros, result[0][0] = blancos registrados, result[0][1] = blancos invitados
    //[[[{blancos - registrados}],[{blancos - invitados}]],[[{negros - registrados}],[{negros - invitados}]]]
    public function actionEquipos(){
        $id_partido = $this->buscarPartidoPorTiempo($_POST['cancha'], $_POST['fecha'], $_POST['hora']);
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $sql = "CALL jugadoresEquipo('b',".$id_partido.")";
            $equipos[0][0] = \Yii::$app->db->createCommand($sql)->queryAll();
            $sql = "CALL invitadosEquipo('b',".$id_partido.")";
            $equipos[0][1] = \Yii::$app->db->createCommand($sql)->queryAll();
            $sql = "CALL jugadoresEquipo('n',".$id_partido.")";
            $equipos[1][0] = \Yii::$app->db->createCommand($sql)->queryAll();
            $sql = "CALL invitadosEquipo('n',".$id_partido.")";
            $equipos[1][1] = \Yii::$app->db->createCommand($sql)->queryAll();
            // $sql = "SELECT c.cupo_max cupo_maximo FROM canchas c, partidos p WHERE p.id_partido = ".$id_partido." AND c.id_cancha = p.id_cancha" ;
            // $equipos[2] = \Yii::$app->db->createCommand($sql)->query();
            $transaction->commit();
            $status = 'ok';
        } catch (Exception $e) {
            $status = 'bad';
            $transaction->rollBack();
        }
        \Yii::$app->response->format = 'json';
        return ['data' => $equipos, 'status' => $status];
    }

    //Esta acción verifica si el el correo y la contraseña enviados coincide con el de algún usuario registrado del
    //sistema. Regresa status = 'ok' y el accessToken si existe, de lo contrario status = 'bad'
    public function actionLogin()
    {//En el local se guardó el accessToken como _chrome-rel-back
        $sql = "SELECT COUNT(*), accessToken FROM usuarios WHERE correo = :correo AND contrasena = :contrasena";
        $query = Yii::$app->db->createCommand($sql)
        ->bindValue(':correo', $_POST['correo'])
        ->bindValue(':contrasena', sha1($_POST['contrasena']));
        $total = $query->queryScalar();
        $access = $query->query();
        if($total > 0){
            return ['status' => 'ok', 'key' => $access];
        }else{
            return ['status' => 'bad'];
        }
    }

    //Esta acción permite registrar a un jugador, devuelve status = 'ok' y el accessToken si se pudo guardar,
    //si no se pudo status = 'bad'
    public function actionRegistrarPerfil()
    {//En el local se guardó el accessToken como _chrome-rel-back
    	\Yii::$app->response->format = 'json';
        // if(Yii::$app->user->can('Administrador')){
        //     return ['mensaje' => 'Eres Administrador'];
        // }else{
        //     return ['mensaje' => 'Tú no eres Administrador, eres jugador'];
        // }
        $model = new Usuario();
        $model->nombres = $_POST['nombres'];
        $model->apellidos = $_POST['apellidos'];
        $model->correo = $_POST['correo'];
        $model->usuario = $_POST['correo'];
        $model->contrasena = sha1($_POST['contrasena']);
        $model->accessToken = md5($model->contrasena);
        $model->telefono = $_POST['telefono'];
        $model->sexo = $_POST['sexo'];
        $model->perfil = 'Jugador';
        if($model->save()){
            $role = Yii::$app->authManager->getRole($model->perfil);
            Yii::$app->authManager->assign($role, $model->id_usuario);
            return ['status' => 'ok', 'mensaje' => 'Guardado correctamente', 'accessToken' => $model->accessToken];
        }else{
            return ['status' => 'bad', 'mensaje' => 'No guardó'/*, 'auth' => Yii::$app->user->identity*/];
        }
    }

    // //Esta acción recibe el id del partido, el equipo (blanco/negro) y los datos del invitado para registrarlo en el partido
    // public function actionRegistrarInvitado(){
    //     $transaction = \Yii::$app->db->beginTransaction();
    //     try {
    //         $invitado = new Invitados();
    //         $invitado->nombres = $_POST['nombres'];
    //         $invitado->apellidos = $_POST['apellidos'];
    //         $invitado->correo = $_POST['correo'];
    //         $invitado->sexo = $_POST['sexo'];
    //         $invitado->telefono = $_POST['telefono'];
    //         if($invitado->save()){
    //             $sql = "INSERT INTO invitaciones (id_usuario, id_invitado, equipo, id_partido) VALUES ('".Yii::$app->user->id."', '".$invitado->id_invitado."', '".strtolower(substr($_POST['equipo'],0,1))."', '".$_POST['partido']."')";
    //             \Yii::$app->db->createCommand($sql)->execute();
    //             $sql = "UPDATE partidos SET ".strtolower($_POST['equipo'])."s = (".strtolower($_POST['equipo'])."s+1) WHERE id_partido = ".$_POST['partido'];
    //             \Yii::$app->db->createCommand($sql)->execute();
    //             $result['status'] = 'ok';
    //         }
    //         $result['id'] = $invitado->id_invitado;
    //         $transaction->commit();
    //     } catch (Exception $e) {
    //         $result['status'] = 'bad';
    //         $transaction->rollBack();
    //     }
    //     $result['nombre'] = $_POST['nombres']." ".$_POST['apellidos'];
    //     \Yii::$app->response->format = 'json';
    //     return $result;
    // }

    // //Esta acción permite actualizar el perfil de un jugador, devuelve status = 'ok' si se pudo guardar, si no se pudo status = 'bad'
    // public function actionActualizarPerfil()
    // {
    //     $model = $this->findModel(Yii::$app->user->id);
    //     $contrasena = $model->contrasena;
    //     $model->nombres = $_POST['nombres'];
    //     $model->apellidos = $_POST['apellidos'];
    //     $model->correo = $_POST['correo'];
    //     ($_POST['contrasena'] === '') ? $model->contrasena = $contrasena : $model->contrasena = sha1($_POST['contrasena']);
    //     $model->usuario = $_POST['correo'];
    //     $model->telefono = $_POST['telefono'];
    //     $model->sexo = $_POST['sexo'];
    //     // $model->accessToken = $model->contrasena;
    //     // $model->perfil = 'Jugador';
    //     if($model->save()){
    //         return ['status' => 'ok', 'mensaje' => 'Actualizado correctamente'];
    //     }else{
    //         return ['status' => 'bad', 'mensaje' => 'No se pudo actualizar'/*, 'auth' => Yii::$app->user->identity*/];
    //     }
    // }

    // //Devuelve la información de un perfil con el último partido jugado
    // public function actionInfoPerfil(){
    //     \Yii::$app->response->format = 'json';
    //     $transaction = \Yii::$app->db->beginTransaction();
    //     try {
    //         $sql = "SELECT CONCAT(nombres, ' ', apellidos) nombre, correo, (if(sexo = 'f','Femenino','Masculino')) sexo, telefono FROM usuarios WHERE id_usuario = ".Yii::$app->user->id;
    //         $user = \Yii::$app->db->createCommand($sql)->queryOne();
    //         $result['data'] = $user;
    //         $sql = "SET lc_time_names = 'es_CO'";
    //         Yii::$app->db->createCommand($sql)->execute();
    //         $sql = "SELECT p.fecha, DATE_FORMAT(p.fecha, '%W %e %M') label_fecha, p.hora, DATE_FORMAT(p.hora, '%r') label_hora FROM usuarios_partidos ut, partidos p WHERE ut.id_usuario = ".
    //         Yii::$app->user->id." AND p.estado = 2 AND ut.id_partido = p.id_partido ORDER BY p.fecha DESC, p.hora DESC LIMIT 0,1";
    //         $last = \Yii::$app->db->createCommand($sql)->queryOne();
    //         $result['ultimo_partido'] = $last;
    //         $transaction->commit();
    //         $result['mensaje'] = 'ok';
    //     } catch (Exception $e) {
    //         $result['mensaje'] = 'bad';
    //         $transaction->rollBack();
    //     }
    //     return $result;
    // }

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
