<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "canchas".
 *
 * @property integer $id_cancha
 * @property string $nombre
 * @property string $direccion
 * @property string $telefono
 * @property integer $cupo_max
 * @property integer $estado
 *
 * @property Estados $estado
 *
 * @property Partidos[] $partidos
 */
class Canchas extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'canchas';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre', 'direccion', 'telefono', 'cupo_max'], 'required'],
            [['cupo_max', 'estado'], 'integer'],
            [['nombre', 'direccion', 'telefono'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_cancha' => 'Id Cancha',
            'nombre' => 'Nombre',
            'direccion' => 'Direccion',
            'telefono' => 'Telefono',
            'cupo_max' => 'Cupo Max',
            'estado' => 'Estado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPartidos()
    {
        return $this->hasMany(Partidos::className(), ['id_cancha' => 'id_cancha']);
    }

    public function getIdEstado()
    {
        return $this->hasOne(Estados::className(), ['id_estado' => 'estado']);
    }

    public function getNombrearchivo($directorio){
        if(file_exists($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl."/images/".$directorio."/".$this->id_cancha.".jpg")){
            return $this->id_cancha.".jpg";
        }
        if(file_exists($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl."/images/".$directorio."/".$this->id_cancha.".png")){
            return $this->id_cancha.".png";
        }
        if(file_exists($_SERVER['DOCUMENT_ROOT'].Yii::$app->request->baseUrl."/images/".$directorio."/".$this->id_cancha.".gif")){
            return $this->id_cancha.".gif";
        }
        return 'default.jpg';
    }
}
