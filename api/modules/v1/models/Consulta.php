<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "consulta".
 *
 * @property integer $id
 * @property string $Fecha
 * @property string $Hora
 * @property string $Cancha
 * @property string $Direccion
 * @property string $Telefono
 * @property integer $Cupo
 * @property string $Total
 * @property integer $Blancos
 * @property integer $Negros
 * @property integer $Estado
 */
class Consulta extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'consulta';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'Cupo', 'Total', 'Blancos', 'Negros', 'Estado'], 'integer'],
            [['Fecha', 'Hora', 'Cancha', 'Direccion', 'Telefono', 'Cupo'], 'required'],
            [['Fecha', 'Hora'], 'safe'],
            [['Cancha', 'Direccion', 'Telefono'], 'string', 'max' => 45]
        ];
    }

     public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'Fecha' => 'Fecha',
            'Hora' => 'Hora',
            'Cancha' => 'Cancha',
            'Direccion' => 'Direccion',
            'Telefono' => 'Telefono',
            'Cupo' => 'Cupo',
            'Total' => 'Total',
            'Blancos' => 'Blancos',
            'Negros' => 'Negros',
            'Estado' => 'Estado',
        ];
    }
}
