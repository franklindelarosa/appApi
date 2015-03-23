<?php

namespace app\api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "estados".
 *
 * @property integer $id_estado
 * @property string $nombre
 * @property string $entidad
 * @property string $descripcion
 *
 * @property Canchas[] $canchas
 * @property Partidos[] $partidos
 * @property Usuarios[] $usuarios
 */
class Estados extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    const PARTIDO_DISPONIBLE = 1;
    const PARTIDO_NO_DISPONIBLE = 2;
    const PARTIDO_CANCELADO = 3;
    const USUARIO_ACTIVO = 4;
    const USUARIO_INACTIVO = 5;
    const USUARIO_BLOQUEADO = 8;
    const CANCHA_ACTIVA = 6;
    const CANCHA_INACTIVA = 7;
    
    public static function tableName()
    {
        return 'estados';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre', 'entidad'], 'required'],
            [['nombre', 'entidad'], 'string', 'max' => 45],
            [['descripcion'], 'string', 'max' => 150],
            [['nombre'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_estado' => 'Id Estado',
            'nombre' => 'Nombre',
            'entidad' => 'Entidad',
            'descripcion' => 'Descripcion',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCanchas()
    {
        return $this->hasMany(Canchas::className(), ['estado' => 'id_estado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPartidos()
    {
        return $this->hasMany(Partidos::className(), ['estado' => 'id_estado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuarios()
    {
        return $this->hasMany(Usuario::className(), ['estado' => 'id_estado']);
    }
}
