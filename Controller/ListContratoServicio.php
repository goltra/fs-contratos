<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Contratos\Model\ContratoServicio;

class ListContratoServicio extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fa-solid fa-file-signature";
        return $data;
    }


    protected function createViews(): void
    {

        $this->createViewsContratoServicio();
    }


    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'renew':
                return $this->renewAction();
        }

        return parent::execPreviousAction($action);
    }



    protected function createViewsContratoServicio($viewName = "ListContratoServicio"): void
    {
        $this->addView($viewName, "ContratoServicio", "Contratos");

        // Para ordenar
        $this->addOrderBy($viewName, ["fecha_renovacion"], "fecha_renovacion");
        $this->addOrderBy($viewName, ["importe_anual"], "importe_anual");
        $this->addOrderBy($viewName, ["fecha_alta"], "fecha_alta");

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
     * @throws Exception
     */
    protected function addRenewButton(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'renew',
            'icon' => 'fa-solid fa-plus',
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
            Tools::log()->error('No hay contrato para renovar.');
            return true;
        }

        if (!$this->request->request->get('date')){
            Tools::log()->error('No has seleccionado una fecha para la factura');
            return true;
        }

        $codes = explode(',', $this->request->request->get('code'));

        if (false === is_array($codes)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        $hasError = false;

        foreach ($codes as $code){
            $contrato = new ContratoServicio();
            $contrato->load($code);

            if ($contrato->hasErrorsToRenew()){
                $hasError = true;
            }
        }

        if ($hasError){
            Tools::log()->error('No se ha renovado ningún contrato. Por favor, soluciona las incidencias y vuelve a intentarlo.');
            return true;
        }


        // comprobamos los codigos por si hay alguno que de error.
        $res = [];

        foreach ($codes as $code){
            $contrato = new ContratoServicio();
            $contrato->load($code);
            $factura = null;

            if (count($res) > 0){
                foreach ($res as $r){
                    if (isset($r['codcliente']) && $r['codcliente'] === $contrato->codcliente && isset($r['idfactura'])){
                        $factura = new FacturaCliente();
                        $factura->load($r['idfactura']);
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
