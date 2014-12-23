<?php

namespace app\api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "invitados".
 *
 * @property integer $id_invitado
 * @property string $nombre
 * @property string $telefono
 * @property string $sexo
 * @property string $correo
 *
 * @property Invitaciones[] $invitaciones
 */
class Invitados extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'invitados';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombres', 'apellidos', 'telefono', 'sexo', 'correo'], 'required'],
            [['nombres', 'apellidos', 'correo'], 'string', 'max' => 45],
            [['telefono'], 'string', 'max' => 20],
            [['sexo'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_invitado' => 'Id Invitado',
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'telefono' => 'Telefono',
            'sexo' => 'Sexo',
            'correo' => 'Correo',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitaciones()
    {
        return $this->hasMany(Invitaciones::className(), ['id_invitado' => 'id_invitado']);
    }
}
