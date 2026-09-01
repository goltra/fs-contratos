<?php

namespace FacturaScripts\Plugins\Contratos;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Dinamic\Lib\API\APIModel;

class Init extends InitClass
{
    public function init(): void
    {
        // se ejecutara cada vez que carga FacturaScripts (si este plugin está activado).
        $this->loadExtension(new Extension\Controller\EditCliente());

        if (method_exists(APIModel::class, 'excludeModel')) {
            APIModel::excludeModel('ContratoServicio');
        }
    }

    public function update(): void
    {
        // se ejecutara cada vez que se instala o actualiza el plugin.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
}
