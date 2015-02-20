<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "posiciones".
 *
 * @property integer $id_posicion
 * @property string $posicion
 *
 * @property Usuarios[] $usuarios
 */
class Posiciones extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'posiciones';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['posicion'], 'required'],
            [['posicion'], 'string', 'max' => 35]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_posicion' => 'Id Posición',
            'posicion' => 'Posición',
        ];
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getUsuarios()
    {
        return $this->hasMany(Usuarios::className(), ['id_posicion' => 'id_posicion']);
    }
}
