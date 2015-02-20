<?php

namespace app\api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "usuarios".
 *
 * @property integer $id_usuario
 * @property string $nombre
 * @property string $usuario
 * @property string $contrasena
 * @property string $sexo
 * @property string $perfil
 * @property integer $estado
 * @property string $telefono
 * @property string $correo
 * @property string $authKey
* @property string $accessToken
 *
 * @property Invitaciones[] $invitaciones
 * @property Estados $estado
 * @property UsuariosPartidos[] $usuariosPartidos
 */
class Usuario extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
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
            [['nombres', 'apellidos', 'usuario', 'contrasena', 'sexo', 'accessToken'], 'required'],
            [['estado'], 'integer'],
            [['nombres', 'apellidos', 'usuario', 'perfil', 'correo', 'accessToken'], 'string', 'max' => 45],
            [['contrasena'], 'string', 'max' => 70],
            [['sexo'], 'string', 'max' => 1],
            [['telefono'], 'string', 'max' => 20],
           [['usuario'], 'unique']
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
            'contrasena' => 'Contrasena',
            'sexo' => 'Sexo',
            'perfil' => 'Perfil',
            'estado' => 'Estado',
            'telefono' => 'Telefono',
            'correo' => 'Correo',
            'accessToken' => 'Access Token',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitaciones()
    {
        return $this->hasMany(Invitaciones::className(), ['id_usuario' => 'id_usuario']);
    }

    public function getEstado()
    {
        return $this->hasOne(Estados::className(), ['id_estado' => 'estado']);
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
    }
    // public static function findIdentityByAccessToken($token, $type = null)
    // {
    //     $usuario = Usuario::find()->where(['accessToken' => $toke])->one();
    //     if ($usuario['accessToken'] !== null) {
    //         return new static($usuario);
    //     }
    //     return null;
    // }

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
