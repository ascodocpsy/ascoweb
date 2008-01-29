<?php

/**
 * Module d'import pour Ascodocpsy
 */
class ImportAsco extends ImportModule
{

    public function preExecute()
    {
        // Chaque membre du GIP ne peut voir que les fichiers qu'il a chargé sur le serveur  
        if (User::hasAccess('Edit'))
            $this->request->add('_filter', 'ident:'.User::get('login'));
        
        parent::preExecute();
    }
}
?>