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
use app\api\modules\v1\models\Posiciones;

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
                'informacion-jugador' => ['post'],
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
        $sql = "SET time_zone = '-05:00'"; //Hora de Colombia
        Yii::$app->db->createCommand($sql)->execute();
        // Resultado por queryBuilder:
        // $query = new Query;
        // $query->select('c.*')->distinct()->from("canchas c")->innerJoin("partidos p", "p.id_cancha = c.id_cancha AND p.estado = :estado AND (CONCAT(p.fecha, ' ', p.hora) > now())");
        // return ['status' => 'ok', 'data' => $query->addParams([':estado' => Estados::PARTIDO_DISPONIBLE])->orderBy(['c.nombre' => SORT_ASC])->all()];

        // Resultado por Data Access Object:
        $sql = "SELECT DISTINCT c.* FROM canchas c, partidos p WHERE c.estado = 6 AND c.id_cancha = p.id_cancha AND p.estado = ".Estados::PARTIDO_DISPONIBLE." AND CONCAT(p.fecha, ' ', p.hora) > now() ORDER BY c.nombre ASC";
        return ['status' => 'ok', 'data' => Yii::$app->db->createCommand($sql)->queryAll(), 'posiciones' => Posiciones::find()->all()];

        // Resultado por ActiveRecords:
        // return Canchas::find()->innerJoinWith([
        //         'partidos' => function ($query){
        //             $query->where('partidos.estado = :estado')->addParams([':estado' => Estados::PARTIDO_DISPONIBLE]);
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
        $sql = "SELECT DISTINCT fecha dia, DATE_FORMAT(fecha, '%W %e de %M') label, (SELECT COUNT(*) FROM partidos WHERE fecha = dia AND id_cancha = :id_cancha) total FROM partidos WHERE estado = :estado AND id_cancha = :id_cancha AND CONCAT(fecha, ' ', hora) > now() ORDER BY fecha ASC ";
        return ['status' => 'ok', 'data' => Yii::$app->db->createCommand($sql)->bindValue(':estado', Estados::PARTIDO_DISPONIBLE)
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
            $sql = "SELECT p.hora, DATE_FORMAT(p.hora, '%h:%i %p') label, p.blancos, p.negros, (p.blancos+p.negros) total, p.venta, (c.cupo_max-(p.blancos+p.negros)) disponibles FROM partidos p, canchas c WHERE p.id_cancha = c.id_cancha AND p.estado = :estado AND p.id_cancha = :id_cancha AND p.fecha = :fecha AND CONCAT(p.fecha, ' ', p.hora) > now() ORDER BY p.hora ASC";
            $result = Yii::$app->db->createCommand($sql)->bindValue(':estado', Estados::PARTIDO_DISPONIBLE)
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
        return ['data' => $equipos, 'status' => $status, 'partido' => $id_partido];
    }

    //Esta acción verifica si el el correo y la contraseña enviados coincide con el de algún usuario registrado del
    //sistema. Regresa status = 'ok' y el accessToken si existe, de lo contrario status = 'bad'
    public function actionLogin()
    {//En el local se guardó el accessToken como _chrome-rel-back
        $sql = "SELECT accessToken, id_usuario FROM usuarios WHERE correo = :correo AND contrasena = :contrasena AND estado = :estado";
        $query = Yii::$app->db->createCommand($sql)
        ->bindValue(':correo', $_POST['correo'])
        ->bindValue(':contrasena', sha1($_POST['contrasena']))
        ->bindValue(':estado', 4);
        $total = $query->query()->getRowCount();
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
        if(isset($_POST['facebook'])){
            $model = Usuario::find()->where("contrasena = sha1(md5('".$_POST['contrasena']."8888'))")->one();
            if($model !== null){
                if($model->estado === Estados::USUARIO_ACTIVO){
                    return ['status' => 'ok', 'key' => $model->accessToken, 'id' => $model->id_usuario];
                }
            }
            if(!isset($_POST['telefono'])){
                return ['status' => 'ok', 'key' => 'no'];
            }
        }
        $model = Usuario::find()->where("correo = '".$_POST['correo']."' OR usuario = '".$_POST['correo']."'")->one();
        if($model === null){
            $model = new Usuario();
            $model->nombres = $_POST['nombres'];
            $model->apellidos = $_POST['apellidos'];
            if(isset($_POST['fecha_nacimiento']) && $_POST['fecha_nacimiento'] !== ''){
                $model->fecha_nacimiento = $_POST['fecha_nacimiento'];
            }
            $model->correo = $_POST['correo'];
            $model->usuario = $_POST['correo'];
            $model->sexo = $_POST['sexo'];
            $model->telefono = $_POST['telefono'];
            $model->accessToken = md5(time().'csrf'.rand());
            if(isset($_POST['foto'])){
                $nombre_foto = md5(time().rand()).'.jpg';
                $model->foto = 'http'.$nombre_foto;
                $model->contrasena = sha1(md5($_POST['contrasena'].'8888'));
                $file = file($_POST['foto']);
                file_put_contents($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl.'/fotos/'.$model->foto, $file);
            }else{
                $model->contrasena = sha1($_POST['contrasena']);
            }
            if(isset($_POST['posicion']) && $_POST['posicion'] !== ''){
                $model->id_posicion = $_POST['posicion'];
            }
            if(isset($_POST['pierna_habil']) && $_POST['pierna_habil'] !== ''){
                $model->pierna_habil = $_POST['pierna_habil'];
            }
            $model->perfil = 'Jugador';
            if($model->save()){
                $role = Yii::$app->authManager->getRole($model->perfil);
                Yii::$app->authManager->assign($role, $model->id_usuario);
                return ['status' => 'ok', 'key' => $model->accessToken, 'id' => $model->id_usuario];
            }else{
                if(isset($_POST['foto'])){
                    unlink($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl.'/fotos/'.$nombre_foto);
                }
                return ['status' => 'bad', 'mensaje' => "No se pudo completar el registro, vuelve a intentarlo"];
            }
        }elseif($model->estado === Estados::USUARIO_INACTIVO || isset($_POST['facebook'])){
            $model->nombres = $_POST['nombres'];
            $model->apellidos = $_POST['apellidos'];
            if(isset($_POST['fecha_nacimiento'])){
                $model->fecha_nacimiento === '' ? $model->fecha_nacimiento = NULL : $model->fecha_nacimiento = $_POST['fecha_nacimiento'];
            }
            $model->sexo = $_POST['sexo'];
            $model->telefono = $_POST['telefono'];
            $model->accessToken = md5(time().'csrf'.rand());
            if(isset($_POST['facebook'])){
                $model->contrasena = sha1(md5($_POST['contrasena'].'8888'));
                $nombre_foto = md5(time().rand()).'.jpg';
                if($model->foto !== 'httpdefault.jpg'){
                    unlink($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl.'/fotos/'.$model->foto);
                }
                $model->foto = 'http'.$nombre_foto;
                $file = file($_POST['foto']);
                file_put_contents($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl.'/fotos/'.$model->foto, $file);
            }else{
                $model->contrasena = sha1($_POST['contrasena']);
                if($model->foto !== 'default.jpg'){
                    unlink($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl.'/fotos/'.$model->foto);
                }
                $model->foto = 'default.jpg';
            }
            if(isset($_POST['posicion'])){
                $model->id_posicion === '' ? $model->id_posicion = 1 : $model->id_posicion = $_POST['posicion'];
            }else{
                $model->id_posicion = 1;
            }
            if(isset($_POST['pierna_habil'])){
                $model->pierna_habil === '' ? $model->pierna_habil = NULL : $model->pierna_habil = $_POST['pierna_habil'];
            }else{
                $model->pierna_habil = NULL;
            }
            $model->estado = Estados::USUARIO_ACTIVO;
            if($model->save()){
                return ['status' => 'ok', 'key' => $model->accessToken, 'id' => $model->id_usuario];
            }else{
                return ['status' => 'bad', 'mensaje' => 'Hubo un error restaurando la cuenta, vuelve a intentarlo'];
            }
        }elseif($model->estado === Estados::USUARIO_BLOQUEADO){
            return ['status' => 'bad', 'mensaje' => "Has sido bloqueado, ponte en contacto con nosotros para mayor información"];
        }else{
            return ['status' => 'bad', 'mensaje' => "Ya existe un usuario asociado con el correo especificado"];
        }
    }

    //Esta acción permite consultar la información de un jugador (usuario/invitado), devuelve status = 'ok' y el data
    //con el tipo de etidad recibido, si no se pudo status = 'bad'
    public function actionInformacionJugador()
    {
        \Yii::$app->response->format = 'json';
        if($_POST['entidad'] === 'usuario'){
            $sql = "SELECT u.nombres, u.apellidos, u.fecha_nacimiento, u.correo, u.perfil, (if(u.sexo = 'f','Mujer','Hombre')) sexo, u.telefono, p.posicion, u.pierna_habil, u.foto FROM usuarios u, posiciones p WHERE u.id_posicion = p.id_posicion AND u.id_usuario = ".$_POST['id'];
            $jugador = \Yii::$app->db->createCommand($sql)->queryOne();
        }else{
            $sql = "SELECT i.nombres, i.apellidos, i.correo, 'Invitado' perfil, (if(i.sexo = 'f','Mujer','Hombre')) sexo, i.telefono, p.posicion, i.pierna_habil, u.nombres resp_nombres, u.apellidos resp_apellidos, u.telefono tel_responsable, 'guest.png' foto FROM invitados i, invitaciones ic, usuarios u, posiciones p WHERE i.id_posicion = p.id_posicion AND u.id_usuario = ic.id_usuario AND i.id_invitado = ic.id_invitado AND ic.id_partido = ".$_POST['partido']." AND i.id_invitado = ".$_POST['id'];
            $jugador = \Yii::$app->db->createCommand($sql)->queryOne();
        }
        return ['status' => 'ok', 'data' => $jugador, 'entidad' => $_POST['entidad']];
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
