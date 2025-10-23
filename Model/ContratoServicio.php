<?php

namespace FacturaScripts\Plugins\Contratos\Model;

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\ListFilter\PeriodTools;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ContratoServicio extends ModelClass
{

    use ModelTrait {
        all as protected traitAll;
    }

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
    public $mostrar_proxima_renovacion_en_factura;

    const ESTADO_LIMITE_RENOVACION_OK = 0;
    const ESTADO_LIMITE_RENOVACION_WARNING = 1;
    const ESTADO_LIMITE_RENOVACION_DANGER = 2;

    public function clear(): void
    {
        parent::clear();
        $this->fecha_alta = Tools::date();
        $this->fecha_renovacion = Tools::date('+1 month');
        $this->importe_anual = 0;
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
    public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array
    {
        $modelList = self::traitAll($where, $order, $offset, $limit);
        $modelListEdited = [];

        if (count($modelList) > 0) {
            foreach ($modelList as $v) {
                $v->estado_limite_renovacion = self::checkLimiteRenovacion(strlen($v->fsiguiente_servicio ?? '') > 0 ? $v->fsiguiente_servicio : $v->fecha_renovacion);
                $modelListEdited[] = $v;
            }
            $modelList = $modelListEdited;
        }

        return $modelList;
    }

    /**
     * Función que comprueba si está en fecha la renovación o no
     * @param $fecha
     * @return int
     */
    static function checkLimiteRenovacion($fecha): int
    {

        $fecha = date('Y-m-d', strtotime($fecha));

        if ($fecha < date('Y-m-d'))
            return self::ESTADO_LIMITE_RENOVACION_DANGER;

        if ($fecha <= date('Y-m-d', strtotime('+1 month')))
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
        $agrupaciones = $dataBase->select("SELECT DISTINCT agrupacion FROM contrato_servicios where agrupacion is not null;");
        $res = [];

        foreach ($agrupaciones as $a) {
            $res[] = ['code' => $a['agrupacion'], 'description' => $a['agrupacion']];
        }

        return $res;
    }


    /**
     * Comprueba las reglas para poder renovar el contrato. En caso de haber error, se muestra tantas notificaciones como errores.
     * @return bool
     */
    public function hasErrorsToRenew(): bool
    {
        $error = false;

        if (strlen($this->codcliente) === 0) {
            Tools::log()->error('Error al comprobar el contrato ' . $this->titulo . ', no hay un cliente vinculado.');
            $error = true;
        }

        if (strlen($this->idproducto) === 0) {
            Tools::log()->error('Error al comprobar el contrato ' . $this->titulo . ', no hay un producto vinculado.');
            $error = true;
        }

        if ($this->periodo === '------') {
            Tools::log()->error('Error al comprobar el contrato ' . $this->titulo . ', no se ha establecido un periodo de renovación.');
            $error = true;
        }

        if (strlen($this->fecha_renovacion) === 0) {
            Tools::log()->error('Error al comprobar el contrato ' . $this->titulo . ', no se ha establecido una fecha de renovación');
            $error = true;
        }

        return $error;
    }


    /**
     * Renueva un contrato
     * 1 - Genera la factura
     * 2 - Actualiza el contrato
     * @param string $invoiceDate
     * @param FacturaCliente|null $factura
     * @return array
     *
     */
    public function renewService(string $invoiceDate, FacturaCliente $factura = null): array
    {

        $renovationDate = date('Y-m-d', strtotime(PeriodTools::applyFormatToDate($this->periodo, 'd-m-Y', $this->fecha_renovacion)));

        $database = new DataBase();
        $database->beginTransaction();

        if ($factura === null) {
            try {
                $factura = $this->generateInvoice($invoiceDate, $renovationDate);
            } catch (Exception $e) {
                $database->rollback();
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        } else {
            $this->addLineToInvoice($factura, $renovationDate);

            if ($this->mostrar_proxima_renovacion_en_factura) {
                $this->addProximaRenovacionLine($factura, $renovationDate);
            }

            $lines = $factura->getLines();
            Calculator::calculate($factura, $lines, true);
        }

        /*
         * Actualizamos el contrato una vez la factura ha sido guardada
         */
        $this->idfactura = $factura->idfactura;
        $this->fecha_renovacion = $renovationDate;

        if ($this->save()) {
            $database->commit();
            return ['status' => 'ok', 'message' => 'Contrato renovado hasta el ' . date('d/m/Y', strtotime($renovationDate)), 'codcliente' => $factura->codcliente, 'idfactura' => $factura->idfactura];
        } else {
            $database->rollback();
            return ['status' => 'error', 'message' => 'Error al actualizar el contrato'];
        }

    }

    /**
     * Genera el contrato
     * @param string $invoiceDate
     * @param string $renovationDate
     * @return FacturaCliente
     * @throws Exception
     */
    public function generateInvoice(string $invoiceDate, string $renovationDate): FacturaCliente
    {
        $factura = new FacturaCliente();

        $cliente = new Cliente();
        $cliente->load($this->codcliente);
        $factura->setSubject($cliente);
        $factura->fecha = $invoiceDate;

        if (strlen($this->codpago) > 0)
            $factura->codpago = $this->codpago;

        if ($factura->save()) {

            if (!$this->addLineToInvoice($factura, $renovationDate))
                throw new Exception('Error al generar la factura, la linea no es correcta.');

            if ($this->mostrar_proxima_renovacion_en_factura) {
                $this->addProximaRenovacionLine($factura, $renovationDate);
            }

            // recalculo los totales
            $lines = $factura->getLines();
            Calculator::calculate($factura, $lines, true);

            $generator = new InvoiceToAccounting();
            $generator->generate($factura);

            if (empty($factura->idasiento) || !$factura->save())
                throw new Exception('Error al guardar el asiento contable.');

            return $factura;
        } else
            throw new Exception('Error al generar la factura.');
    }


    /**
     * Genera una linea de la factura con los datos del contrato
     * @param FacturaCliente $factura
     * @param string $renovationDate
     * @return bool
     */
    public function addLineToInvoice(FacturaCliente $factura, string $renovationDate): bool
    {
        $producto = new Producto();
        $producto->load($this->idproducto);

        $linea = $factura->getNewProductLine($producto->referencia);

        $linea->descripcion = (strlen($this->producto_descripcion) > 0 ? $this->producto_descripcion : $producto->descripcion);

        if (!$this->mostrar_proxima_renovacion_en_factura)
            $linea->descripcion .= ' - desde el ' . $this->fecha_renovacion . ' al ' . date('d-m-Y', strtotime($renovationDate));

        $linea->cantidad = 1;
        $linea->codimpuesto = $producto->codimpuesto;

        if ($this->importe_anual > 0)
            $linea->pvpunitario = $this->importe_anual;

        return $linea->save();
    }

    /**
     * Pone una línea en blanco indicando la próxima renovación
     * @param FacturaCliente $factura
     * @param string $renovationDate
     * @return bool
     */
    private function addProximaRenovacionLine(FacturaCliente $factura, string $renovationDate): bool
    {
        $linea = $factura->getNewLine();
        $linea->idfactura = $factura->idfactura;
        $linea->descripcion = 'Próxima renovación ' . date('d-m-Y', strtotime($renovationDate));
        return $linea->save();
    }

}
