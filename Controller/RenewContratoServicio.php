<?php
namespace FacturaScripts\Plugins\MyNewPlugin\Controller;

use FacturaScripts\Core\Base\Controller;

class RenewContratoServicio extends Controller {

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fas fa-file-signature";
        $data["showonmenu"] = false;
        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->init();
    }

    private function init(){
        $res = $this->request->request->get('res');
        parse_str($res, $output);

        var_dump($output);

    }
}
