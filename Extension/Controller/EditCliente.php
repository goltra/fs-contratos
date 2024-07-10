<?php
namespace FacturaScripts\Plugins\Contratos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class EditCliente
{
    public function createViews(): Closure
    {
        return function() {
            $viewName = 'ListContratoServicio';
            $this->addListView($viewName, 'ContratoServicio', 'Contratos', 'fa-solid fa-file-contract');
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



