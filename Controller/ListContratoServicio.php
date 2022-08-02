<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Contratos\Model\ContratoServicio;

class ListContratoServicio extends ListController
{
    public function getPageData() {
        $data = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fas fa-file-signature";
        return $data;
    }


    protected function createViews() {

        $this->createViewsContratoServicio();
    }


    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'renew':
                return $this->renewAction();
        }

        return parent::execPreviousAction($action);
    }



    protected function createViewsContratoServicio($viewName = "ListContratoServicio")
    {
        $this->addView($viewName, "ContratoServicio", "Contratos");

        // Para ordenar
        $this->addOrderBy($viewName, ["fecha_renovacion"], "fecha_renovacion");

        // filtro general
        $this->addSearchFields($viewName, ['titulo', 'observaciones']);

        // filters
        $this->addFilterAutocomplete($viewName, 'codcliente', 'cliente', 'codcliente', 'clientes', 'codcliente', 'nombre');

        //        Para que nos e vea siempre la sección de filtros abierta
        $this->addFilterSelectWhere($viewName, 'suspendido', [
            ['label' => 'Activo', 'where' => [new DataBaseWhere('suspendido', false)]],
            ['label' => 'Suspendido', 'where' => [new DataBaseWhere('suspendido', true)]],
        ]);


        $this->addFilterSelect($viewName, 'agrupacion', 'agrupacion', 'agrupacion', ContratoServicio::getAgrupacionToDropDown());

        $this->addRenewButton($viewName);
    }


    /**
     * Add an modal button for renumber entries
     *
     * @param string $viewName
     */
    protected function addRenewButton(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'renew',
            'icon' => 'fas fa-plus',
            'label' => 'Renovar y generar factura',
            'type' => 'modal',
            'color' => 'info'
        ]);
    }


    /**
     * @return bool
     */
    protected function renewAction(): bool
    {
        if (!$this->request->request->get('code')){
            $this->Toolbox()->log()->error('No hay contrato para renovar.');
            return true;
        }

        if (!$this->request->request->get('date')){
            $this->Toolbox()->log()->error('No has seleccionado una fecha para la factura');
            return true;
        }

        $codes = explode(',', $this->request->request->get('code'));

        if (false === is_array($codes)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        $hasError = false;

        foreach ($codes as $code){
            $contrato = new ContratoServicio();
            $contrato->loadFromCode($code);

            if ($contrato->hasErrorsToRenew()){
                $hasError = true;
            }
        }

        if ($hasError){
            $this->Toolbox()->log()->error('No se ha renovado ningún contrato. Por favor, soluciona las incidencias y vuelve a intentarlo.');
            return true;
        }


        // comprobamos los codigos por si hay alguno que de error.
        $res = [];

        foreach ($codes as $code){
            $contrato = new ContratoServicio();
            $contrato->loadFromCode($code);
            $factura = null;

            if (count($res) > 0){
                foreach ($res as $r){
                    if (isset($r['codcliente']) && $r['codcliente'] === $contrato->codcliente && isset($r['idfactura'])){
                        $factura = new FacturaCliente();
                        $factura->loadFromCode($r['idfactura']);
                        break;
                    }
                }
            }

            $res[$code] = array_merge($contrato->renewService($this->request->request->get('date'), $factura), ['idcontrato' => $code]);
        }

        $this->redirect('RenewContratoServicio?'.http_build_query(array('params' => $res)));

        return true;

    }

}
