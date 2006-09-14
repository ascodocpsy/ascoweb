<?php
Config::addArray
(
    array
    (
        // Paramètres du cache
        'cache'=>array
        (
            // Indique si on autorise ou non le cache (true/false)
            'enabled'   => true,
            
            // Path du répertoire dans lequel seront stockés les fichiers mis
            // en cache. Il peut s'agir d'un chemin absolu (c:/temp/cache/) ou
            // d'un chemin relatif à la racine de l'application ($root)
            'path'      => 'cache'
        )
    )
);
?>
