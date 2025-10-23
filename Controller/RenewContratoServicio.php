<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;


class RenewContratoServicio extends Controller {

    public array $renovados = [];
    public array $noRenovados = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Contratos";
        $data["menu"] = "sales";
        $data["icon"] = "fa-solid fa-file-signature";
        $data["showonmenu"] = false;
        return $data;
    }

    /**
     * @throws KernelException
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);
        $this->init();
    }

    private function init(): void
    {
        $res = $this->request->query->get('params');

        $this->renovados = array_filter($res, function ($c){ return $c['status'] === 'ok'; });
        $this->noRenovados = array_filter($res, function ($c){ return $c['status'] === 'error'; });

    }
}
