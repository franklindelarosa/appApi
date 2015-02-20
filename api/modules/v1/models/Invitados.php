<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "invitados".
 *
 * @property integer $id_invitado
 * @property string $nombres
 * @property string $apellidos
 * @property string $telefono
 * @property string $sexo
 * @property string $correo
 * @property integer $id_posicion
 * @property string $pierna_habil
 * @property string $fecha_nacimiento
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
            [['id_posicion'], 'integer'],
            [['fecha_nacimiento'], 'safe'],
            [['nombres', 'apellidos', 'correo'], 'string', 'max' => 45],
            [['telefono'], 'string', 'max' => 20],
            [['sexo'], 'string', 'max' => 1],
            [['pierna_habil'], 'string', 'max' => 15]
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
            'id_posicion' => 'Id Posición',
            'pierna_habil' => 'Pierna Hábil',
            'fecha_nacimiento' => 'Fecha Nacimiento',
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
