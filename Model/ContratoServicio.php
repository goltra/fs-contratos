<?php
namespace FacturaScripts\Plugins\GoltratecServicios\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

class ContratoServicio extends ModelClass
{

    use ModelTrait;

    public $idcontrato;
    public $codcliente;
    public $codagente;
    public $fecha_alta;
    public $fecha_renovacion;
    public $observaciones;
    public $codpago;
    public $importe_anual;
    public $periodo;
    public $fsiguiente_servicio;
    public $titulo;
    public $suspendido;
    public $idfactura;
    public $estado_limite_renovacion;
    public $agrupacion;

    CONST ESTADO_LIMITE_RENOVACION_OK = 0;
    CONST ESTADO_LIMITE_RENOVACION_WARNING = 1;
    CONST ESTADO_LIMITE_RENOVACION_DANGER = 2;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function clear() {
        parent::clear();
    }

    public static function primaryColumn() {
        return "idcontrato";
    }

    public static function tableName() {
        return "contrato_servicios";
    }

    /**
     * Sobrescribimos la función all para agregar el estado del límite de la renovación
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50)
    {
        $modelList = parent::all($where, $order, $offset, $limit);
        $modelListEdited = [];


        if(count($modelList) > 0){
            foreach ($modelList as $v){
                $v->estado_limite_renovacion = $this->checkLimiteRenovacion(strlen($v->fsiguiente_servicio) > 0 ? $v->fsiguiente_servicio : $v->fecha_renovacion);
                $modelListEdited[] = $v;
            }
            $modelList = $modelListEdited;
        }

        return  $modelList;
    }

    /**
     * Función que comprueba si está en fecha la renovación o no
     * @param $fecha
     * @return int
     */
    private function checkLimiteRenovacion($fecha){

        $fecha = date('Y-m-d', strtotime($fecha));

        if($fecha < date('Y-m-d'))
            return self::ESTADO_LIMITE_RENOVACION_DANGER;

        if($fecha <= date('Y-m-d', strtotime('+1 month')))
            return self::ESTADO_LIMITE_RENOVACION_WARNING;

        return self::ESTADO_LIMITE_RENOVACION_OK;

    }


    /**
     * Devuelve las agrupaciones
     * @return array
     */
    static function getAgrupacionToDropDown(){
        $dataBase = new DataBase();
        $agrupaciones = $dataBase->select('SELECT DISTINCT agrupacion FROM contrato_servicios where agrupacion is not null;');
        $res = [];

        foreach ($agrupaciones as $a){
            $res[] = ['code' => $a['agrupacion'], 'description' => $a['agrupacion']];
        }

        return $res;
    }

//    en codeModelSearch puedes sobrescribir valores de vuelta de un modelo

}
