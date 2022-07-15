<?php
namespace FacturaScripts\Plugins\Contratos\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Lib\ListFilter\PeriodTools;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;

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
    public $idproducto;
    public $producto_descripcion;

    const ESTADO_LIMITE_RENOVACION_OK = 0;
    const ESTADO_LIMITE_RENOVACION_WARNING = 1;
    const ESTADO_LIMITE_RENOVACION_DANGER = 2;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }


    public function clear() {
        parent::clear();
    }

    /**
     * @return string
     */
    public static function primaryColumn(): string
    {
        return "idcontrato";
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
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
    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array
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
    private function checkLimiteRenovacion($fecha): int
    {

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
    public static function getAgrupacionToDropDown(): array
    {
        $dataBase = new DataBase();
        $agrupaciones = $dataBase->select('SELECT DISTINCT agrupacion FROM contrato_servicios where agrupacion is not null;');
        $res = [];

        foreach ($agrupaciones as $a){
            $res[] = ['code' => $a['agrupacion'], 'description' => $a['agrupacion']];
        }

        return $res;
    }


    /**
     * Renueva un contrato
     * 1 - Genera la factura
     * 2 - Actualiza el contrato
     * @param $code
     * @param $date
     * @return string[]
     */
    static function renewService($code, $date): array
    {

        $contrato = new ContratoServicio();
        $contrato->loadFromCode($code);

        if (strlen($contrato->codcliente) === 0)
            return ['status' => 'error', 'message' => 'Error al generar la factura, cliente no vinculado al contrato.'];

        if (strlen($contrato->idproducto) === 0)
            return ['status' => 'error', 'message' => 'Error al generar la factura, producto no vinculado al contrato.'];

        if (strlen($contrato->periodo) === 0)
            return ['status' => 'error', 'message' => 'Error al generar la factura, no se ha establecido un periodo de renovación.'];

        if (strlen($contrato->fecha_renovacion) === 0)
            return ['status' => 'error', 'message' => 'Error al generar la factura, no se ha establecido una fecha de renovación.'];


        $fechaAnterior = $contrato->fecha_renovacion;
        $fechaRenovacion = date('Y-m-d', strtotime(PeriodTools::applyFormatToDate($contrato->periodo, 'd-m-Y', $fechaAnterior)));


        $factura = new FacturaCliente();

        $database = new DataBase();
        $database->beginTransaction();

        $cliente = new Cliente();
        $cliente->loadFromCode($contrato->codcliente);
        $factura->setSubject($cliente);
        $factura->fecha = $date;

        if (strlen($contrato->codpago) > 0)
            $factura->codpago = $contrato->codpago;


        if ($factura->save()){
            $linea = $factura->getNewLine();
            $producto = new Producto();
            $producto->loadFromCode($contrato->idproducto);

            $linea->idproducto = $producto->idproducto;
            $linea->idfactura = $factura->idfactura;
            $linea->referencia = $producto->referencia;
            $linea->descripcion = (strlen($contrato->producto_descripcion) > 0 ? $contrato->producto_descripcion : $producto->descripcion) . ' - desde '.$fechaAnterior. ' a '.$fechaRenovacion;
            $linea->cantidad = 1;
            $linea->pvpunitario = $contrato->importe_anual > 0 ? $contrato->importe_anual : $producto->precio;
            $linea->pvptotal = $contrato->importe_anual > 0 ? $contrato->importe_anual : $producto->precio;
            $linea->codimpuesto = $producto->getTax()->codimpuesto;

            if (!$linea->save()){
                $database->rollback();
                return ['status' => 'error', 'message' => 'Error al generar la factura, la linea no es correcta.'];
            }

            // recalculo los totales
            $tool = new BusinessDocumentTools();
            $tool->recalculate($factura);

            $generator = new InvoiceToAccounting();
            $generator->generate($factura);

            if (empty($factura->idasiento) || !$factura->save()) {
                $database->rollback();
                return ['status' => 'error', 'message' => 'Error al guardar el asiento contable.'];
            }

            /*
             * Actualizamos el contrato una vez la factura ha sido guardada
             */
            $contrato->idfactura = $factura->idfactura;
            $contrato->fecha_renovacion = $fechaRenovacion;

            if ($contrato->save()){
                $database->commit();
                return ['status' => 'ok', 'message' => 'Contrato renovado hasta el '.date('d/m/Y', strtotime($fechaRenovacion))];
            }
            else{
                $database->rollback();
                return ['status' => 'error', 'message' => 'Error al actualizar el contrato'];
            }
        }
        else {
            $database->rollback();
            return ['status' => 'error', 'message' => 'Error al generar la factura'];
        }
    }

//    en codeModelSearch puedes sobrescribir valores de vuelta de un modelo

}
