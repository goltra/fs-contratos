<?php

namespace FacturaScripts\Plugins\Contratos;

use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        // se ejecutara cada vez que carga FacturaScripts (si este plugin está activado).
        $this->loadExtension(new Extension\Controller\EditCliente());
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
