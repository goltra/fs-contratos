<?php

namespace FacturaScripts\Plugins\Contratos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Contratos\Model\ContratoServicio;

class EditCliente
{
    public function createViews(): Closure
    {
        return function () {
            $viewName = 'ListContratoServicio';
            $this->addListView($viewName, 'ContratoServicio', 'Contratos', 'fa-solid fa-file-contract')
                ->addSearchFields(['titulo', 'observaciones'])
                ->addFilterSelectWhere('suspendido', [
                    ['label' => 'Activo', 'where' => [new DataBaseWhere('suspendido', false)]],
                    ['label' => 'Suspendido', 'where' => [new DataBaseWhere('suspendido', true)]],
                ])
                ->addFilterSelect('agrupacion', 'agrupacion', 'agrupacion', ContratoServicio::getAgrupacionToDropDown())
                ->disableColumn('customer');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view): void {
            if ($viewName === 'ListContratoServicio') {
                $code = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                $where = [new DataBaseWhere('codcliente', $code)];
                $view->loadData('', $where, []);
            }
        };
    }
}
