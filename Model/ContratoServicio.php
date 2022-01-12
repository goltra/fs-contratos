<?php
namespace FacturaScripts\Plugins\GoltratecServicios\Model;

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

//    en codeModelSearch puedes sobrescribir valores de vuelta de un modelo

}
