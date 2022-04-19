<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
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
    }

}
