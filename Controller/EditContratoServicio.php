<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

class EditContratoServicio extends \FacturaScripts\Core\Lib\ExtendedController\EditController
{
    public function getModelClassName() {
        return "ContratoServicio";
    }

    public function getPageData() {
        $data = parent::getPageData();
        $data["title"] = "ContratoServicio";
        $data["icon"] = "fas fa-search";
        return $data;
    }





}
