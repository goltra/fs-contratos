<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use FacturaScripts\Core\Base\Controller;

class RenewContratoServicio extends Controller {

    public $renovados = [];
    public $noRenovados = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fas fa-file-signature";
        $data["showonmenu"] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->init();
    }

    private function init(){
        $res = $this->request->query->get('params');

        $this->renovados = array_filter($res, function ($c){ return $c['status'] === 'ok'; });
        $this->noRenovados = array_filter($res, function ($c){ return $c['status'] === 'error'; });

    }
}
