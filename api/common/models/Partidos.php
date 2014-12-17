<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "partidos".
 *
 * @property integer $id_partido
 * @property string $fecha
 * @property string $hora
 * @property string $costo
 * @property string $venta
 * @property integer $estado
 * @property integer $blancos
 * @property integer $negros
 * @property integer $id_cancha
 *
 * @property Estados $estado
 * @property Canchas $cancha
 * @property UsuariosPartidos[] $usuariosPartidos
 */
class Partidos extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'partidos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fecha', 'hora', 'costo'], 'required'],
            [['fecha', 'hora'], 'safe'],
            [['costo', 'venta'], 'number'],
            [['estado', 'blancos', 'negros', 'id_cancha'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_partido' => 'Id Partido',
            'fecha' => 'Fecha',
            'hora' => 'Hora',
            'costo' => 'Costo',
            'venta' => 'Venta',
            'estado' => 'Estado',
            'blancos' => 'Blancos',
            'negros' => 'Negros',
            'id_cancha' => 'Cancha',
            // 'canchaName' => Yii::t('app', 'idCancha'),
            // 'estadoName' => Yii::t('app', 'estado'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdCancha()
    {
        return $this->hasOne(Canchas::className(), ['id_cancha' => 'id_cancha']);
    }

    public function NameCancha()
    {
        return $this->idCancha->nombre;
    }

    public function getIdEstado()
    {
        return $this->hasOne(Estados::className(), ['id_estado' => 'estado']);
    }

    public function NameEstado()
    {
        return $this->idEstado->nombre;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuariosPartidos()
    {
        return $this->hasMany(UsuariosPartidos::className(), ['id_partido' => 'id_partido']);
    }
}
