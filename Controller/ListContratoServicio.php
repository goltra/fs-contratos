<?php
namespace FacturaScripts\Plugins\GoltratecServicios\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;

class ListContratoServicio extends \FacturaScripts\Core\Lib\ExtendedController\ListController
{
    public function getPageData() {
        $data = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fas fa-search";
        return $data;
    }

    protected function createViews() {

        $this->createViewsContratoServicio();
    }



    protected function createViewsContratoServicio($viewName = "ListContratoServicio")
    {
        $this->addView($viewName, "ContratoServicio", "Contratos");

        // Para ordenar
        $this->addOrderBy($viewName, ["fecha_renovacion"], "fecha_renovacion");

        // filtro general
        $this->addSearchFields($viewName, ['titulo']);

        // filters
        $this->addFilterAutocomplete($viewName, 'codcliente', 'cliente', 'codcliente', 'clientes', 'codcliente', 'nombre');
        $this->addFilterCheckbox($viewName, 'suspendido', 'suspendido', 'suspendido', '=', true, [new DataBaseWhere('suspendido', 0, '=')]);
    }

}
