<?php
namespace FacturaScripts\Plugins\Contratos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Contratos\Model\ContratoServicio;

class EditCliente
{
    public function createViews(): Closure
    {
        return function() {
            $viewName = 'ListContratoServicio';
            $this->addListView($viewName, 'ContratoServicio', 'Contratos', 'fa-solid fa-file-contract');
            $this->tab($viewName)->addSearchFields(['titulo', 'observaciones']);
//            $this->tab($viewName)->addFilterCheckbox('suspendido', 'suspendido', 'suspendido');
            $this->tab($viewName)->addFilterSelectWhere('suspendido', [
                ['label' => 'Activo', 'where' => [new DataBaseWhere('suspendido', false)]],
                ['label' => 'Suspendido', 'where' => [new DataBaseWhere('suspendido', true)]],
            ]);
            $this->tab($viewName)->addFilterSelect('agrupacion', 'agrupacion', 'agrupacion', ContratoServicio::getAgrupacionToDropDown());
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'ListContratoServicio') {
                $code = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                $where = [new DataBaseWhere('codcliente', $code)];
                $view->loadData('', $where, []);
            }
        };
    }
}



