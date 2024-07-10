<?php
namespace FacturaScripts\Plugins\Contratos;

class Init extends \FacturaScripts\Core\Base\InitClass
{
    public function init() {
        $this->loadExtension(new Extension\Controller\EditCliente());
        // se ejecutara cada vez que carga FacturaScripts (si este plugin está activado).
    }

    public function update() {
        // se ejecutara cada vez que se instala o actualiza el plugin.
    }
}
