<?php
namespace FacturaScripts\Plugins\Contratos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Plugins\Contratos\Model\ContratoServicio;

class EditContratoServicio extends EditController
{

    public function getModelClassName(): string
    {
        return "ContratoServicio";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "ContratoServicio";
        $data["icon"] = "fas fa-search";
        return $data;
    }

    public function createViews()
    {
        parent::createViews(); // TODO: Change the autogenerated stub
    }


    protected function execPreviousAction($action)
    {
        if ($action === 'renew'){
            $this->renewAction();
            return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function execAfterAction($action)
    {
        $contrato = new ContratoServicio();
        $contrato->loadFromCode($this->request->query->get('code'));

        if (isset($contrato->suspendido) && $contrato->suspendido === false )
            $this->addButton('EditContratoServicio', [
                'action' => 'renew',
                'icon' => 'fas fa-plus',
                'label' => 'Renovar y generar factura',
                'type' => 'modal',
                'color' => 'info'
            ]);

        parent::execAfterAction($action);
    }


    /**
     * Función para renovar el servicio, crea la factura y actualiza el contrato.
     */
    private function renewAction()
    {

        if (!$this->request->query->get('code')){
            $this->Toolbox()->log()->error('No hay contrato para renovar.');
            return;
        }

        if (!$this->request->request->get('date')){
            $this->Toolbox()->log()->error('No has seleccionado una fecha para la factura');
            return;
        }

        $res = ContratoServicio::renewService($this->request->query->get('code'), $this->request->request->get('date'));

        switch ($res['status']){
            case 'error':
                $this->Toolbox()->log()->error($res['message']);
                break;

            case 'ok':
                $this->Toolbox()->log()->notice($res['message']);
                break;
        }
    }

}
