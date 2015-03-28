<?php

namespace app\api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "usuarios".
 *
 * @property integer $id_usuario
 * @property string $nombres
 * @property string $apellidos
 * @property string $usuario
 * @property string $contrasena
 * @property string $sexo
 * @property string $perfil
 * @property integer $estado
 * @property string $telefono
 * @property string $correo
 * @property integer $id_posicion
 * @property string $fecha_nacimiento
 * @property string $pierna_habil
 * @property string $foto
 * @property string $accessToken
 * @property string $authPass
 *
 * @property Invitaciones[] $invitaciones
 * @property Estados $estado
 * @property Posiciones $idPosicion
 * @property UsuariosPartidos[] $usuariosPartidos
 */
class Usuario extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    public $authKey;
    // public $accessToken;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'usuarios';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombres', 'apellidos', 'usuario', 'contrasena', 'sexo', 'correo', 'accessToken'], 'required'],
            [['estado', 'id_posicion'], 'integer'],
            [['fecha_nacimiento'], 'safe'],
            [['nombres', 'apellidos', 'usuario', 'perfil', 'correo'], 'string', 'max' => 45],
            [['contrasena', 'accessToken'], 'string', 'max' => 100],
            [['sexo'], 'string', 'max' => 1],
            [['telefono'], 'string', 'max' => 20],
            [['usuario'], 'unique'],
            [['correo'], 'unique'],
            [['pierna_habil'], 'string', 'max' => 15],
            [['foto'], 'string', 'max' => 100],
            [['authPass'], 'string', 'max' => 200],
            [['accessToken'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_usuario' => 'Id Usuario',
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'usuario' => 'Usuario',
            'contrasena' => 'Contraseña',
            'sexo' => 'Sexo',
            'perfil' => 'Perfil',
            'estado' => 'Estado',
            'telefono' => 'Teléfono',
            'correo' => 'Correo',
            'id_posicion' => 'Posición',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'pierna_habil' => 'Pierna Hábil',
            'foto' => 'Foto',
            'accessToken' => 'Access Token',
            'authPass' => 'Llave de autorización',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitaciones()
    {
        return $this->hasMany(Invitaciones::className(), ['id_usuario' => 'id_usuario']);
    }

    public function getIdEstado()
    {
        return $this->hasOne(Estados::className(), ['id_estado' => 'estado']);
    }

    /**
    * @return \yii\db\ActiveQuery
    */
   public function getIdPosicion()
   {
       return $this->hasOne(Posiciones::className(), ['id_posicion' => 'id_posicion']);
   }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuariosPartidos()
    {
        return $this->hasMany(UsuariosPartidos::className(), ['id_usuario' => 'id_usuario']);
    }

    public function getId()
    {
        return $this->id_usuario;
    }

    public static function findIdentity($id)
    {
        // return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
        $usuario = Usuario::find()->where(['id_usuario' => $id])->one();
        if ($usuario !== null) {
            return new static($usuario);
        }
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['accessToken' => $token]);
        // $usuario = Usuario::find()->where(['accessToken' => $token])->one();
        // if ($usuario['accessToken'] !== null) {
        //     return new static($usuario);
        // }
        // return null;
    }

    public function getUsername(){
        return $this->usuario;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }
    
    public function validatePassword($password)
    {
        return $this->contrasena === sha1($password);
    }

    public static function findByUsername($username)
    {
        $usuario = Usuario::find()->where(['usuario' => $username])->one();
        if ($usuario !== null) {
            return new static($usuario);
        }
        return null;
    }
}
