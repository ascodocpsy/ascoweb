<?php
/**
 * Module de consultation du thesaurus SantéPsy. 
 */
class Thesaurus extends ThesaurusModule
{
    /**
     * Importe le thesaurus SantéPsy dans une base de données.
     * 
     * Le chargement du thesaurus se fait à partir des listes hiérarchique et
     * alphabétique exportées depuis Cindoc.
     * 
     * 
     * @param string $alpha le nom du fichier contenant la liste alphabétique.
     * @param string $hiera le nom du fichier contenant la liste hiérarchique.
     */
//     * Utilise la classe {@link ThesaurusCindoc}.
    
    public function actionImportAsco($alpha='', $hiera='')
    {
        if ($alpha==='' || $hiera==='')
        {
            $files=array();
            foreach (glob(Utils::makePath(Runtime::$root, 'data', 'thesaurus', '*.*')) as $path)
            {
                $files[]=basename($path);
            }
            
            Template::run
            (
                'import.html',
                array('files'=>$files)
            );
            return;
        }
        echo '<h1>Chargement du thesaurus</h1>';
        echo '<p>Liste alphabétique : ', $alpha, '</p>';
        echo '<p>Liste hiérarchique : ', $hiera, '</p>';

        require_once dirname(__FILE__).'/ThesaurusCindoc.php';
        
        // Path du fichier thesaurus à importer
        $alphaPath=Utils::makePath(Runtime::$root, 'data', 'thesaurus', $alpha);
        $hierPath=Utils::makePath(Runtime::$root, 'data', 'thesaurus', $hiera);

        // Charge le fichier thesaurus
        $theso=new ThesaurusCindoc($alphaPath, $hierPath);

        // Ouvre la base
        $this->openDatabase(false);
        
        // Charge tous les termes dans la base
        $nb=0;
        foreach($theso->getTerms() as $fre=>$term)
        {
            // Crée une nouvelle notice
            $this->selection->addRecord();
            
            // Initialise tous les champs
            foreach($term as $rel=>$value)
                $this->selection[$rel]=$value;
            
            // Ajoute la notice dans la base
            $this->selection->saveRecord();

            // Affiche les termes
            $nb++;
            echo $nb, ' ', $fre, '<br />';
        }
        
        // Ferme le fichier thesaurus
        unset($theso);

        // Ferme la base
        unset($this->selection);
    }
    
}
?>
