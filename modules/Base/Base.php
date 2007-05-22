<?php

/**
 * Module Base - Consultation de la base documentaire
 * Transfert vers BisDatabase
 */

class Base extends DatabaseModule
{
    // TODO : A la place du template 'templates/error/error.yaml' mettre en place un système de message d'erreur.
    
    // TODO : Faire en sorte de récupérer le séparateur d'articles (à partir
    // du .def, de la config, ...
    // Séparateur d'articles
    const SEPARATOR=' / ';
    
    private $error='';

    // Constantes et variables pour l'import de notices
    const dataPath='data/import/';
    const fileList='files.list';
    
    private $files=array();
    private $adminFiles=array();
    
    /**
     * Correspondances entre les champs des fichiers de notices
     * et les champs de la base
     */
    private $map=array();
    
    /**
     * Identifiant de la personne connectée
     */
    private $ident;
    
    private $tblAsco=array();
    
    public function preExecute()
    {
     //   if ($this->action=='exportCart')
     //       $this->actionExportCart(true);
        if (Utils::isAjax())
        {
            $this->setLayout('none');
            Config::set('debug', false);
            Config::set('showdebug', false);
            header('Content-Type: text/html; charset=ISO-8859-1'); // TODO : avoir une rubrique php dans general.yaml permettant de "forcer" les options de php.ini
            if ($this->action==='search') Config::set('template','templates/answers.html');
        }
         
        if ($this->action=='exportCartByType')
            $this->actionExportCartByType(true);

        // Récupère l'identifiant
        $this->ident=strtolower(User::get('login'));

        // Charge la table de correspondances entre le numéro d'un centre (ascoX)
        // et son numéro de l'article sur le site Ascodocpsy
        $this->tblAsco=$this->loadTable('annuairegip');
        
        // TODO : je ne veux pas de ça ! (le jour où ils ajoutent un champ, faut mettre à jour le script)
        // prendre tous les champs de la base un par un et les mettre en maju, on devrait avoir la même chose
        $this->map=array
        (
            'ANNEXE' => 'Annexe',
            'AUT' => 'Aut',
            'CANDES' => 'CanDes',
            'COL' => 'Col',
            'CONGRDAT' => 'CongrDat',
            'CONGRLIE' => 'CongrLie',
            'CONGRNUM' => 'CongrNum',
            'CONGRTIT' => 'CongrTit',
            'DATE' => 'Date',
            'DATETEXT' => 'DateText',
            'DATEPUB' => 'DatePub',
            'DATEVALI' => 'DateVali',
            'DIPSPE' => 'DipSpe',
            'EDIT' => 'Edit',
            'ETATCOL' => 'EtatCol',
            'ISBNISSN' => 'IsbnIssn',
           // 'LIENANNE' => 'LienAnne',  // Supprimé, les liens sont mis dans le champ Tit
            'LIEU' => 'Lieu',
            'LOC' => 'Loc',
            'MOTCLE' => 'MotCle',
            'NATTEXT' => 'NatText',
            'NOMP' => 'Nomp',
            'NOTES' => 'Notes',
            'NUM' => 'Num',
            'NUMTEXOF' => 'NumTexOf',
            'PAGE' => 'Page',
            'PDPF' => 'PdPf',
            'PRODFICH' => 'ProdFich',
            'REED' => 'Reed',
            'RESU' => 'Resu',
            'REV' => 'Rev',
            'THEME' => 'Theme',
            'TIT' => 'Tit',
            'TYPE' => 'Type',
            'LIEN' => 'Lien',
            'VIEPERIO' => 'ViePerio',
            'VOL' => 'Vol'
        );
    }

/*
 * Transforme un fichier texte tabulé en tableau
 * Le fichier texte est de la forme suivante :
 * 1ère ligne : Entête1[tab]Entête2
 * lignes suivante : Valeur1[tab]Valeur2
 */
    private function loadTable($source)
    {
        $t=array();
        
        // Ajoute l'extension par défaut s'il y a lieu
        $source=Utils::defaultExtension($source, '.txt');
                        
        // Détermine le path exact de la table
        $h=Utils::searchFile
        (
            $source,                                    // On recherche la table :
            //dirname(self::$stateStack[1]['template']),  // dans le répertoire du script appellant
            Runtime::$root . 'tables',                  // dans le répertoire 'tables' de l'application
            Runtime::$fabRoot . 'tables'                // dans le répertoire 'tables du framework
        );
        if (! $h)
            throw new Exception("Table non trouvée : '$source'");
        
        $file=@fopen($h, 'r');
        if ($file === false)
            throw new Exception('Impossible d\'ouvrir le fichier '. $source);

        // Lit la ligne d'entête
        $fields=fgetcsv($file, 4096, "\t", '"');

        // Lit les enregistrements
        while (($data=fgetcsv($file, 4096, "\t", '"')) !== false)
            $t[$data[0]]=$data[1];

        // Ferme la table
        fclose($file);
    
        return $t;
    }


    public function getField($name)
    {
        switch ($name)
        {
            case 'Annexe':
                // TODO : revoir : ne marche pas avec Titre de l'annexe1 <http://www.lien.fr >/Titre de l'annexe2/< http://www.lien2.fr>
                // Lien vers texte intégral
                // Syntaxe du champ :
                // Titre de l'annexe1 <http://www.lien.fr>/Titre de l'annexe2/<http://www.lien2.fr>
                $value='';
                $h=$this->selection[$name];
                while (strlen($h)>0)
                {
                    $pt=strpos($h, '>');
                    // Cas 1 : on a Titre de l'annexe1 <http://www.lien.fr> ou <http://www.lien2.fr>
                    if ($pt !== false)
                    {
                        // Extrait le titre et son lien
                        $art=substr($h, 0, $pt);                        // Titre de l'annexe1 <http://www.lien.fr
                        $tit=trim(substr($art, 0, strpos($art, '<')));  // Titre de l'annexe1
                        $lien=trim(substr($art, strpos($art, '<')+1));  // http://www.lien.fr
                        if (! $tit) $tit=$lien;
                        if ($value) $value.=' ; ';
                        $value.=$this->link($tit, $lien, 'Accéder au texte intégral (ouverture dans une nouvelle fenêtre)', true);
                        $h=trim(substr($h, $pt+1));
                        if (strpos($h, self::SEPARATOR)==0)
                            $h=trim(substr($h, 1));
                    }
                    // Cas 2 : on a uniquement Titre
                    else
                    {
                        $pt=strpos($h, self::SEPARATOR);
                        if ($pt === false)
                            $pt=strlen($h);
                        $tit=trim(substr($h, 0, $pt));
                        if ($value) $value.=' ; ';
                        $value.=$tit;
                        $h=trim(substr($h, $pt+1));
                    }
                }
                return $value;
    
            case 'Aut':
                if (! $h=$this->selection[$name]) return '';
                
                $t=explode(trim(self::SEPARATOR),$h);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    if ($aut=$this->author($h))
                    {
                        $lien='search?aut='. urlencode('"'.$aut.'*"');
                        $h=$this->link($h, $lien, 'Bibliographie de '.$h);
                    }
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
            
            case 'Page':
                if (! $h=$this->selection[$name]) return '';
                
                if (stripos($h, 'p.') === false && stripos($h, 'pagination') === false)
                    return trim($h).' p.';
                return '';
            
            case 'PageEdit':
                if (! $page=$this->selection['Page']) return '';
                
                if ($this->selection['Type'] == 'Rapport')
                {
                    if ($h=$this->selection['Lieu'].$this->selection['Edit'].$this->selection['Reed'])
                        return (stripos($page, 'p.') === false && stripos($page, 'pagination') === false) ? trim($page).' p.' : $page;
                }
                return '';    
            
            case 'PageRev':
                if (! $page=$this->selection['Page']) return '';
                
                if ($this->selection['Type'] == 'Rapport')
                {
                    if ($h=$this->selection['Rev'].$this->selection['Vol'].$this->selection['Num'])
                        return (stripos($page, 'p.') === false && stripos($page, 'pagination') === false) ? trim($page).' p.' : $page;
                }
                return '';    
                
            case 'MotCle':
            case 'Nomp':
            case 'CanDes':
            case 'Theme':
                if (! $h=$this->selection[$name]) return '';
    
                $t=explode(trim(self::SEPARATOR), $h);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    $lien='search?motscles='. urlencode($h);
                    $h=$this->link($h, $lien, 'Notices indexées au descripteur '.$h);
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Rev':
                // Lien vers une nouvelle recherche "notices de ce périodique"
                if (! $h=trim($this->selection[$name])) return '';
                $lien='search?rev='. urlencode(Utils::convertString($h,'lower'));
                return $this->link($h, $lien, 'Notices du périodique '.$h);
                      
            case 'DateText':
            case 'DatePub':
            case 'DateVali':
            case 'Creation':
            case 'LastUpdate':
                if (! isset($this->selection)) return '';
                // Affiche les dates AAAA-MM-JJ et AAAAMMJJ sous la forme JJ/MM/AAAA
                if (! $h=$this->selection[$name]) return '';
                return preg_replace('~(\d{4})[-]?(\d{2})[-]?(\d{2})~', '${3}/${2}/${1}', $h);

            case 'Loc':
            case 'ProdFich':
                if (! $h=$this->selection[$name]) return '';
                
                $t=explode(trim(self::SEPARATOR),$h);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    
                    // Recherche le numéro de l'article correspondant à l'URL de la fiche de présentation du centre
                    // et construit le lien
                    if (isset ($this->tblAsco[$h]))
                    {
                        $lien=Config::get('urlarticle').$this->tblAsco[$h];
                        $h=$this->link($h, $lien, 'Présentation du centre '.$h);
                        $t[$key]=$h;
                    }
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Presentation':
                if (! $h=$this->selection['Rev']) return '';
                
                // Notice d'un document type Périodique
                if (Utils::convertString($this->selection['Type'])=='periodique')
                {
                    if (! $lien=$this->selection['Lien']) return '';
                    
                    if (strpos(strtolower($lien), 'ascodocpsy') !== false)
                    {
                        $title='Présentation du périodique';
                        $newwin=false;
                    }
                    else
                    {
                        $title='Accéder au texte intégral (ouverture dans une nouvelle fenêtre)';
                        $newwin=true;
                    }
                    // Lien vers la page de présentation de la revue ou vers le texte intégral
                    return $this->link('&nbsp;<span>Présentation</span>', $lien, $title, $newwin, 'inform');
                }
                else
                {
                    // Lien vers la page de présentation de la revue sur le site d'Ascodocpsy
                    $lien='inform?rev='. urlencode($h);
                    return $this->link('&nbsp;<span>Présentation</span>', $lien, 'Présentation du périodique', false, 'inform');
                }

            case 'EtatCol':
                if (! $t=$this->selection[$name]) return '';
                
                $t=explode(trim(self::SEPARATOR),$t);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    
                    // Extrait le numéro du centre asco (ex : "08 : 1996-2002(lac.)")
                    $length= (strpos($h, ':') === false) ? strlen($h) : strpos($h, ':');
                    $savCentre=trim(substr($h, 0, $length));    // 08
                    
                    // Construit le nom du centre
                    $centre= (substr($savCentre, 0, 1) == '0') ? 'asco'.substr($savCentre, 1) : 'asco'.$savCentre;  // asco8
                    
                    // Recherche l'URL de la fiche de présentation du centre correspondant au numéro asco
                    if (isset ($this->tblAsco[$centre]))
                    {
                        $lien=Config::get('urlarticle').$this->tblAsco[$centre];
                        $savCentre=$this->link($savCentre, $lien, 'Présentation du centre '.$centre);
                        $t[$key]=$savCentre.' '.substr($h, $length);
                    }
                }
                return implode('<br />', $t);
                
            // utilisé pour afficher une erreur dans les templates d'erreurs éventuellement
            // indiqués dans la configuration
            case 'error':
            case 'message':
                return;
            
//            default:
//                if ($this->selection[$name])
//                    return $this->selection[$name];
//                else
//                    return '';

        }
    }

    
    // Utilisé uniquement pour actionLoad et actionLoadFull (cf config.yaml)
    public function getDates($name)
    {        
        switch ($name)
        {
            case 'Creation':
            case 'LastUpdate':
                if (! isset($this->selection) || ! $h=$this->selection[$name]) return;
                // Affiche les dates AAAAMMJJ sous la forme JJ/MM/AAAA
                return preg_replace('~(\d{4})(\d{2})(\d{2})~', '${3}/${2}/${1}', $h);
            
            case 'Type':
                return Utils::get($_REQUEST['Type'], $this->selection['Type']);
                        
            default:
                return;                 
        }
    }
    
    
    // Filtre de validation des champs avant enregistrement dans la base
    // retourne true pour les champs dont on accepte la modif, false sinon
    public function validData($name, &$value)
    {                    
        switch ($name)
        {
            case 'FinSaisie':
                $value=is_null($value) ? 0 : 1;
                break;
            
            case 'Valide':
                if (User::hasAccess('AdminBase'))
                    $value=is_null($value) ? 0 : 1;
                else
                    $value=0;
                    // Si la notice est modifiée par un membre du GIP, la notice repasse
                    // en statut "à valider par un administrateur"
                break;
            
            case 'Creation':
                if ($this->selection[$name]) return false;
                $value=date('Ymd');
                break;

            case 'LastUpdate':
                $value=date('Ymd');
                break;
                
//            default: return false;
        }
        return true;
    }
   
    /**
     * Détermine les utilisateurs qui ont le droit de modifier une notice
     */
 	public function hasEditRight()
 	{
 		// Administrateurs
 		if (User::hasAccess('AdminBase')) return true;
 		
		// Membres du GIP
 		if (User::hasAccess('EditBase'))
 		{
			// Si la saisie de la notice n'est pas terminée, seul les membres 
			// spécifiés dans le champ ProdFich peuvent modifier la notice
	        if ($this->selection['FinSaisie'] == 0)
	        {
	            $t=split(trim(self::SEPARATOR), $this->selection['ProdFich']);
	            return (in_array($this->ident, $t)) ? true : false;
	        }
 
			// Si la saisie de la notice est terminée, tous les membres peuvent modifier la notice
			return true;
 		}
 		
 		return false;
 	}
 
    public function actionLocate()
    {                       
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de périodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de périodique n'a été précisé.");
        
        // Construit l'équation de recherche
        $eq='rev="'.$rev.'" et Type=periodique';
        
        // Recherche la fiche Périodique
        if (! $this->openSelection($eq))
            return $this->showError("Aucune réponse. Equation : $eq");
//        $this->selection=self::openDatabase($eq);
//        if (is_null($this->selection)) return;

        switch ($this->selection->count())
        {
            case 0:
                return $this->showError('Aucune localisation n\'est disponible pour le périodique '.$rev.'.');
            
            default:
                $revinit=$rev;
                $rev=Utils::convertString($rev);
                
                foreach( $this->selection as $record)
                {
                    if ($rev == Utils::convertString($this->selection['Rev']))
                    {
                        // Réouvre la sélection contenant uniquement la notice du périodique
                        if (! $this->openSelection('REF='. $this->selection['REF'], true))
                            return $this->showError("Aucune réponse. Equation : $this->equation");

//                        $this->selection=self::openDatabase('REF='. $this->selection->field(1), true);
//                        if (is_null($this->selection)) return;
                        
                        // Détermine le template à utiliser
                        if (! $template=$this->getTemplate())
                            throw new Exception('Le template à utiliser n\'a pas été indiqué');

                        // Détermine le callback à utiliser
                        $callback=$this->getCallback();

                        // Exécute le template
                        Template::run
                        (
                            $template,  
                            array($this, $callback),
                            $this->selection->record,
                            array('selection',$this->selection)  
                        );

                        exit;                        
                    }
                }
                return $this->showError('Aucune localisation n\'est disponible pour le périodique '.$revinit.'.');
        };
    }
    
    public function actionInform()
    {              
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de périodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de périodique n'a été précisé.");
        
        // Construit l'équation de recherche
        $eq='rev="'.$rev.'" et Type=periodique et Lien=ascodocpsy';
        
        // Recherche la fiche Périodique
        if (! $this->openSelection($eq))
            return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$rev.'.');
            
//        $selection=self::openDatabase($eq);
//        if (is_null($selection)) return;

        // En génaral, on obtient d'autres réponses que celles attendues : par exemple on recherche la revue "soins"
        // (rev="soins") et on va obtenir les revues "soins", "Soins infirmier", "soins soins"
        // on balaye donc la liste des réponses pour rechercher LA réponse qui correspond exactement à la
        // revue recherchée
        $revinit=$rev;
        $rev=Utils::convertString($rev);
        foreach($this->selection as $record)
        {
            if ($rev == Utils::convertString($this->selection['Rev']))
            {
                // Réouvre la sélection contenant uniquement la notice du périodique
                if (! $this->openSelection('REF='. $this->selection['REF'], true))
                    return $this->showError("Aucune réponse. Equation : $eq");
                
                // Redirige vers l'URL du champ Lien (lien sur le site ascodocpsy.org)
                Runtime::redirect($this->selection['Lien'], true);

                exit;
            }
        }
        return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$revinit.'.');
    }
    
    private function author($value)
    {
        $value=(str_ireplace(
            array
            (
                '[s.n.]',
                'collectif',
                'collab.',
                'coord.',
                'dir.',
                'ed.',
                'ill.',
                'préf.',
                'trad.'
            ),
            null,
            $value));
        
        $value=preg_replace('~(.+)(?:(né|née)[ ].+)~','$1', $value);
        
        return trim($value);
        
//        return trim(preg_replace(
//            array
//            (
//                '~(.+)(?:(né|née)[ ].+)~',
//                '/[s.n.]/',
//                '/collectif/i',
//                '/collab/i',
//                '/coord/i',
//                '/dir/i',
//                '/ed/i',
//                '/ill/i',
//                '/préf/i',
//                '/trad/i'
//            ),
//            array
//            (
//                '$1',
//                '',
//                '',
//                '',
//                '',
//                '',
//                '',
//                '',
//                '',
//                ''
//            ),
//            $value));
    }
    
    private function link($value, $lien, $title, $newwin=false, $class='')
    {
        $win=($newwin) ? ' onclick="window.open(this.href); return false;"' : '';
        $c=($class) ? ' class="'.$class.'"' : '';
        return '<a'. $c. ' href="' . Routing::linkFor($lien) . '"' . $win . ' title="'.$title.'">'.$value.'</a>';        
    }

    /**
     * Affiche le formulaire permettant de choisir le type du document à créer
     * Surcharge l'action new de la classe DatabaseModule
     */    
    public function actionNew()
    {       
        // si type non renseigné, affiche le formulaire pour le choisir
        if (is_null(Utils::get($_REQUEST['Type'])))
        {
            Template::run
            (
                'templates/chooseType.html'
            );
        }
        else    // sinon, appelle l'action par défaut
        {
            parent::actionNew();
        }
    }
     
     
     /**
      * Callback qui retourne une chaîne vide pour chaque champ de la nouvelle notice à créer
      */
     public function emptyString($name)
     {
        if($name==='Type')
            return Utils::get($_REQUEST['Type']);
        else
            return '';
     }

    // ------------------- GESTION DU PANIER -------------------
   
    private function exportCart($type, $format)
    {
        global $selection;
     
        if (is_null($type))
            throw new Exception('Le type de document n\'a pas été indiqué.');

        if (is_null($format))
            throw new Exception('Le format d\'export n\'a pas été indiqué.');

        // Récupère le panier du type $type
        $this->getCart();
        $carts=$this->cart->getItems();
        $cart=$carts[$type];

        // Construit l'équation de recherche
        $equation='';
        foreach ($cart as $ref)
        {
            if ($equation) $equation.=' ou ';
            $equation.='ref='.$ref;
        }
        $selection=self::openDatabase($equation);

        // Génère l'export
        $template=$this->path . 'templates/export/'.$format;
        ob_start();
        Template::run
        (
            $template,
            'Template::selectionCallback'
        );
        $data=ob_get_clean();
        return $data;
    }

    // Déchargement des notices par type de document
    public function actionExportCartByType($now=false)
    {
        if (! $now) return; // preExecute nous appelle avec now=true, on bosse, quand le framework nous appelle, rien à faire 
        // tout est fait dans preExecute

        // Récupère le format d'export
        $format=Utils::get($_REQUEST['format']);
        if (is_null($format))
            // TODO : le message d'erreur s'affiche en premier sur la page html
            return $this->showError('Le format d\'export n\'a pas été indiqué.');

        // Charge la liste des formats d'export disponibles
        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
        if (! $format=Config::get("formats.$format"))
            throw new Exception('Format incorrect');

//        $template=$this->path . 'templates/export/'.$format['template'];
//        if (! file_exists($template))
//            throw new Exception('Le fichier contenant les différents formats n\'existe pas');

        global $cart;
        $this->getCart();
        $cart=$this->cart->getItems();

        $data='';
        // Récupère le type de document
        $type=Utils::get($_REQUEST['type']);

        if (is_null($type))
            return $this->showError('Le type du document n\'a pas été indiqué.');

        if (! isset($cart[$type]))
            return $this->showError('Le panier ne contient aucun document du type indiqué.');
        
        // Récupère le template
        if (is_array($format['template']))
        {
            if (isset($format['template'][$type]))
                $template=$format['template'][$type];              
            elseif (isset($format['template']['default']))
                $template=$format['template']['default'];
        }
        else
            $template=$format['template'];

        // Récupère le nom du fichier d'export
        if (is_array($format['filename']))
        {
            if (isset($format['filename'][$type]))
                $filename=$format['filename'][$type];              
            elseif (isset($format['filename']['default']))
                $filename=$format['filename']['default'];
        }
        else
            $filename=$format['filename'];

        if (isset($format['layout']))
            $this->setLayout($format['layout']);
        else
            $this->setLayout($format['none']);

        if (isset($format['content-type']))
            header('content-type: ' . $format['content-type']);

        header
        (
            'content-disposition: attachment; filename="' 
            . (isset($filename) ? $filename : "notices{$type}.txt")
            . '"'
        );
            
        // Génère le contenu du fichier
        $data=$this->exportCart($type, $template);              

        echo $data;
    }
    
   // public function actionExportCart($now=false)
    public function actionExportCart()
    {
        //if (! $now) return; // preExecute nous appelle avec now=true, on bosse, quand le framework nous appelle, rien à faire 
        // tout est fait dans preExecute

        // Récupère l'action à effectuer
        $cmd=Utils::get($_REQUEST['cmd']);
        if (is_null($cmd))
            throw new Exception("La commande n'a pas été indiquée");
            
        if ($cmd !='export' && $cmd!='mail')
            throw new Exception('Commande incorrecte');

        // Récupère le format d'export
        $format=Utils::get($_REQUEST['format']);
        if (is_null($format))
            // TODO : le message d'erreur s'affiche en premier sur la page html
            return $this->showError('Le format d\'export n\'a pas été indiqué.');

        // Charge la liste des formats d'export disponibles
        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
        if (! $format=Config::get("formats.$format"))
            throw new Exception('Format incorrect');

//        $template=$this->path . 'templates/export/'.$format['template'];
//        if (! file_exists($template))
//            throw new Exception('Le fichier contenant les différents formats n\'existe pas');

        global $cart;
        $this->getCart();
        $cart=$this->cart->getItems();

        switch ($cmd)
        {
            // Envoie les notices par mail
            case 'mail':
                // Récupère des informations pour l'envoi du mail
                $to=Utils::get($_REQUEST['to']);
                if (is_null($to))
                    // TODO : le message d'erreur s'affiche en premier sur la page html
                    return $this->showError('Le destinataire du mail n\'a pas été indiqué.');
    
                $subject=Utils::get($_REQUEST['subject']);
                if (is_null($subject))
                    $subject='Notices Ascodocpsy';
    
                $body=Utils::get($_REQUEST['body']);
                if (is_null($body))
                    $body='Le fichier ci-joint contient les notices sélectionnées';
                
                $data='';

                $this->setLayout('none');
    
                require_once(Runtime::$fabRoot.'lib/htmlMimeMail5/htmlMimeMail5.php');
            
                $mail = new htmlMimeMail5();
                //TODO : changer l'adresse e-mail
                $mail->setHeader('Content-Type', 'multipart/mixed');
                $mail->setFrom('Site AscodocPsy <gfaure@ch-st-jean-de-dieu-lyon.fr>');
                $mail->setSubject($subject);
                $mail->setText($body);
                
                // Génère les fichiers attachés
                $this->getCart();
                $carts=$this->cart->getItems();
                
                // Parcourt chaque panier
                foreach ($carts as $type=>$cart)
                {                
                    // Récupère le template
                    if (is_array($format['template']))
                    {
                        if (isset($format['template'][$type]))
                            $template=$format['template'][$type];              
                        elseif (isset($format['template']['default']))
                            $template=$format['template']['default'];
                    }
                    else
                        $template=$format['template'];
    
                    // Récupère le nom du fichier d'export
                    if (is_array($format['filename']))
                    {
                        if (isset($format['filename'][$type]))
                            $filename=$format['filename'][$type];              
                        elseif (isset($format['filename']['default']))
                            $filename=$format['filename']['default'];
                    }
                    else
                        $filename=$format['filename'];
    
                    // Génère le contenu du fichier
                    $data=$this->exportCart($type, $template);              
    
                    $mail->addAttachment
                    (
                        new stringAttachment
                        (
                            $data,
                            isset($filename) ? $filename : "notices{$type}.txt",
                            isset($format['content-type']) ? $format['content-type'] : 'text/plain'
                        )
                    );
                }
                
                if ($mail->send( array($to)) )
                {
                    echo '<p>Vos notices ont été envoyées à l\'adresse ', $to, '</p>';
                    echo '<p>Retour à la <a href="javascript:history.back()"> page précédente</a>.</p>';
                }
                else
                {
                    echo "<p>Impossible d'envoyer le mail à l'adresse '$to'</p>";
                }
                break;

            // Affiche la liste des paniers (en fonction du type de document, avec liens pour télécharger les notices
            case 'export':
                // Définit le template d'affichage 
                if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
                    $tpl='member';
                else
                    $tpl='public';
                $tpl.='_cart_type.yaml';
                
                global $cart;
                $this->getCart();
                $cart=$this->cart->getItems();

                // Exécute le template
                Template::run
                (
                    "templates/$tpl", 
                    array($this, 'getField'),
    //                'Template::selectionCallback',
                    array
                    (
                        'cart'=>$this->cart->getItems(),
                        'format'=>$_REQUEST['format']
                    )
                );            
                break;
        }
    }           
           
    // ------------------- TRI DE LA BASE -------------------
    /**
     * Trie la base, selon la clé de tri sortkey défini dans le fichier
     * de configuration.
     */
    public function actionSortDb()
    {
		// TODO : à supprimer
		set_time_limit(0);

        $start_time=microtime(true);
        
        // Ouvre la base
        $database=Config::get('database');
        if (is_null($database))
            throw new Exception('La base de données à utiliser n\'a pas été indiquée dans le fichier de configuration du module');
        
        $this->selection=Database::open($database, true);

        if (! $this->selection->search('*', array('_max'=>-1)))
        	die('La base à trier ne contient aucun enregistrement.');

        // Vérrouille la base
        // TODO : Faire le lock sur la base si pas déjà fait
        
        // Crée la clé de tri
        $sortKey=$this->createSortKey(Config::get('sortkey'));
        
        // Parcourt toute la sélection en créant les clés de tri
        TaskManager::progress('1. Calcul des clés de tri...', $this->selection->count());
        $i=0;
		$sort=array();
		foreach ($this->selection as $rank=>$record)
		{
		    $sort[$record['REF']]=$this->getKey($sortKey, $record);
		    TaskManager::progress(++$i, 'Notice ' . $record['REF']);
		}

        // Trie les clés
        TaskManager::progress('2. Tri des clés...');
        asort($sort);
        
        // Crée et ouvre la base résultat
        // Pour le moment, on part d'une base vide
        // Copie la base vide vers la base résultat
        // TODO : Ecrire un createdatabase
        $dbPath=Runtime::$root . "data/db/$database.bed";
        $dbSortPath=Runtime::$root . "data/db/$database.sort.bed";
        
        TaskManager::progress('3. Création de la base vide...');
        if (! copy(Runtime::$root . "data/db/${database}Vide.bed", $dbSortPath))
            throw new Exception('La copie de la base n\'a pas réussi.');       
        
        //ascodocpsysort
        $selSort=Database::open($dbSortPath, false, 'bis');
        if (is_null($selSort))
        	throw new Exception('Impossible d\'ouvrir la base résultat.');
        
        // Génère la base triée
        TaskManager::progress('4. Réécriture des enregistrements selon l\'ordre de tri...', count($sort));
        
        $ref=1;
        foreach ($sort as $key=>$value)
        {
            if(! $this->selection->search("REF=$key"))
            	die('ref non trouvée');
            	
            $selSort->addRecord();
			foreach($this->selection->record as $fieldName=>$fieldValue)
			    if ($fieldName!=='REF') $selSort[$fieldName]=$fieldValue;
            $selSort->saveRecord();
            
            if ($ref==1) break;
            
            TaskManager::progress($ref, "Notice $ref");
            $ref++;
        }
        echo '<p>tri réalisé en '. number_format(microtime(true) - $start_time, 2, '.', '')
             . '&nbsp;secondes</p>';
       
        TaskManager::progress('5. Fermeture et flush des bases...');

        // Ferme la base non triée
        unset($record);
        unset($this->selection->record);
        unset($this->selection);
        
        // Ferme la base triée
        unset($selSort->record);
        unset($selSort);
        
        // Supprime la base non triée
        if (! unlink($dbPath))
            throw new Exception('La base non triée n\'a pas pu être supprimée.');
        
        // Renomme la base triée
        if (! rename($dbSortPath, $dbPath))
            throw new Exception('La base triée n\'a pas pu être renommée.');
        
        // Dévérrouille la base
        // TODO : Faire un unlock sur la base
        TaskManager::progress('Terminé');
        
        // TODO : Faire en sorte d'avoir un lien http://xxx
        //echo '<a href="'.Routing::linkFor('/base/search').'">Interroger la nouvelle base...</a>';
    }
    
    /**
     * Crée un tableau contenant les clés utilisées pour le tri, à partir
     * de la chaîne passée en paramètre
     * 
     * @param string $sortKey chaîne contenant les clés de tri, écrites sous la
     * forme Champ1:Champ2:Champ3,Longueur de la clé (entier),Type,Ordre de tri (+ ou -);Autre clé
     * 
     * @return array tableau contenant les clés de tri.
     * i => fieldnames => tableau contenant les champs utilisés pour construire la clé
     *                    array(0=>Champ1, 1=>Champ2, 2=>Champ3)
     *      length     => longeur de la clé de tri
     *      type       => type à utiliser pour créer la clé
     *      order      => ordre du tri : ascendant (+) ou descendant (-)
     */
    private function createSortKey($sortKey)
    {
        $keys=array();
        
        // Ajoute le champ REF comme dernier champ de la clé
        $sortKey.=';REF,6,KeyInteger,+';
        
        // Initialise tous les champs qui composent la clé
        $t=split(';', $sortKey);
        foreach ($t as $key=>$value)
        {
            $items=split(',', trim($value));
            
            // Extrait les noms de champs
            $keys[$key]['fieldnames']=split(':', trim($items[0]));
            
            // Extrait la longueur de clé
            $keys[$key]['length']=trim($items[1]);
            
            // Extrait le type
            $keys[$key]['type']=trim($items[2]);
            
            // Extrait l'ordre de tri
            $keys[$key]['order']=trim($items[3]);
        }
        
        // Retourne le résultat
        return $keys;
    }
        
    /**
     * Crée la clé de l'enregistrement en cours de la sélection $selection
     * 
     * @param array $key tableau contenant les clés de tri
     * @param $selection la sélection en cours
     * 
     * @return string la clé de l'enregistrement en cours 
     */
    private function getKey($key, $selection)
    {
        $getKey='';
        for ($i=0;$i<=count($key)-1;$i++)
        {
            // Récupère le premier champ rempli parmi la liste de champs
            for ($j=0;$j<=count($key[$i]['fieldnames'])-1;$j++)
            {
                $value=$selection[$key[$i]['fieldnames'][$j]];
                if (strlen($value))
                    break;
            }

            // Récupère la longueur de la clé
            $nb=$key[$i]['length'];
            
            // Construit la clé
            switch ($key[$i]['type'])
            {
                // Prendre le champ tel quel sur n caractères
                case 'KeyText':
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    break;

                // Idem mais ignorer la casse des caractères
                case 'KeyTextIgnoreCase':
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    $value=Utils::convertString($value, 'lower');
                    break;
            
                // Prendre le premier article tel quel et padder sur n caractères
                case 'KeyArticle':
                    // TODO : remplacer la chaîne du séparateur par la variable
                    $pt=strpos($value, trim(self::SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    break;
                    
                // Idem mais ignorer la casse des caractères
                case 'KeyArticleIgnoreCase':
                    // TODO : remplacer la chaîne du séparateur par la variable
                    $pt=strpos($value, trim(self::SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    $value=Utils::convertString($value, 'lower');
                    break;

                // Traiter comme un entier (padder avec des zéros sur n caractères)
                case 'KeyInteger':
                    $pt=strpos($value, trim(self::SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, '0', STR_PAD_LEFT);
                    break;
            
                // Traiter comme un champ date au format AAAAMMJJ
                case 'KeyDate':
                    $value=strtr($value, '/-', '');      // AAAA/MM/JJ et AAAA-MM-JJ -> AAAAMMJJ
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');                    
                    break;
                    
                default:
                    throw new Exception('Le type du champ n\'a pas été précisé.');
                    break;
                    // TODO : que faire par défaut ?
            }
            
            // Si tri descendant, commute la clé
            if ($key[$i]['order']=='-')
                $this->switchKey($value);
			
            $getKey.=$value;
        }
        return $getKey;
    }

    private function switchKey(&$value)
    {
        if (! $value) return '';
        
        $value=str_split($value);
        foreach($value as $key=>$char)
            $value[$key]=chr(255-ord($char));
        $value=implode('',$value);
    }
    
    // ------------------- IMPORT DE NOTICES -------------------
         
    // affiche la liste des fichiers à importer
    public function actionImport()
    {
/*
 
 upload : uploader des fichiers
 import : lancer l'import
 delete : supprimer un fichier de la liste   
 */
/*
echo '<pre><big>';
var_export($_REQUEST);
echo '</pre>';
*/
        // TODO : Améliorer le module d'import :
        // - Permettre de choisir les fichiers à importer (case à cocher)
        // - Avoir une case à cocher "Marquer les notices comme validées"
        // - Avoir une case à cocher "Lancer le tri après l'import"
        // - Avoir la possibilité de lancer un tri à tout moment
        
        $errors=array();
        
        // Importe les fichiers et trie la base
        if (Utils::get($_REQUEST['import']))
        {
            // Vérifie qu'il y a des fichiers à importer
            if (count($this->makeList())==0)
            {
                $errors[]= 'Il n\'y a aucun fichier à importer.';
                //Template::run('templates/import/import.yaml','Template::varCallback');
                Template::run
                (
					'templates/import/import.html',
					array
					(
						'errors'=>$errors,
						'files'=>$this->files
					)
				);
                return;
            }
            
            // Vérifie que le gestionnaire de tâche est démarré
            if (! TaskManager::isRunning())
                throw new Exception('Le gestionnaire de tâches n\'est pas démarré.');

            // Définit le moment du lancement de l'import : maintenant ou plus tard
            switch (Utils::get($_REQUEST['now']))
            {
                case 1: // maintenant
                    $id=TaskManager::addTask('/base/importfiles', 0, null, 'Import des fichiers de notices');
                    Runtime::redirect('/taskmanager/taskstatus?id='.$id);
                    break;
                
                case 0: // plus tard
                    // Récupère la date et l'heure de lancement
                    // TODO : Vérifier que $datetime est dans le format attendu
                    $datetime = Utils::get($_REQUEST['delay'], '');
                    if (strlen(trim($datetime))==0)
                        throw new Exception('La date et l\'heure du lancement de l\'import n\'ont pas été indiquées.');
                    list($day, $month, $year, $hour, $minutes, $seconds) = split('[/: ]', $datetime);
                    
                    //checkdate
                    //^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[0-2])/20[0-9]{2}[ ]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$
                    
                    // Calcul le timestamp
                    $timestamp=mktime($hour, $minutes, $seconds, $month, $day, $year);
                    
                    $id=TaskManager::addTask('/base/importfiles', $timestamp, null, 'Import des fichiers de notices');
                    Runtime::redirect('/taskmanager/');
                    break;
                    
                default:
                    throw new Exception('Choix non valide pour définir le moment du lancement de l\'import');
            }
            
            return;
        }
        
        // Charge la liste des fichiers à importer
        // Les administrateurs voient tous les fichiers
        // Chaque membre voit uniquement ses propres fichiers
        $dir=Runtime::$root . self::dataPath . $this->ident;
        $path=$dir . '/' . self::fileList;

        if (file_exists($path))
        {
            $this->files=unserialize(file_get_contents($path));
        }
        else
        {
            // Crée le répertoire de stockage des fichiers (un répertoire par identifiant)
            if (! is_dir($dir)) Utils::makeDirectory($dir);
            $this->files=array();
        }
        
        // On a des fichiers uploadés -> ajoute à la liste
        if (count($_FILES)!=0)
            $this->upload($errors);

        // Enregistre la liste
        file_put_contents(Runtime::$root . self::dataPath . $this->ident . '/' . self::fileList, serialize($this->files));

        // Initialise $files avec la liste complète des fichiers, pour affichage
        // Pour les administrateurs, $files contient l'ensemble des fichiers chargés sur le serveur
        $files=$this->makeList();
        if (User::hasAccess('AdminBase')) $this->adminFiles=$files;
        
        // Supprime un fichier de la liste et enregistre la liste
        // Pour les administrateurs :
        // Pour les membres : appellé avec delete=i -> supprime de la liste le ième fichier 
        $delete=Utils::get($_REQUEST['delete']);
        if (! is_null($delete))
            $this->delete($delete);
        
        // Initialise à nouveau $files avec la liste complète des fichiers, pour affichage
        $files=$this->makeList(); // HACK: les templates ne savent looper que sur des globaux
        if (User::hasAccess('AdminBase')) $this->adminFiles=$files;

        // Affiche la liste et le reste
        // Si administrateur, affiche l'ensemble des listes
        Template::run('templates/import/import.html',array('files'=>$files, 'errors'=>$errors));
    }
    
    /**
     * Récupère les fichiers chargés uploadés
     * 
     * @param array $errors en sortie la liste des erreurs éventuelles rencontrées
     */
    private function upload(array & $errors=null)
    {
        foreach($_FILES as $file)
        {
            switch($file['error'])
            {
                case UPLOAD_ERR_OK:
                    //$path=Runtime::$root . self::dataPath . $file['name'];
                    $path=Runtime::$root . self::dataPath . $this->ident . '/' . $file['name'];
                    $h='';
                    if ($this->isValid($file['tmp_name'], $h)== false)
                    {
                        $errors[] = "Le fichier '" . $file['name'] . "' n'est pas valide : $h";
                    }
                    else
                    {
                        if (move_uploaded_file($file['tmp_name'], $path)==false)
                            $errors[] = "Impossible d'enregistrer le fichier '" . $file['name'] . "'.";
                        else
                        {
                            $index=count($this->files);
                            $this->files[$index]=array
                            (
                                'index'=>$index,
                                'ident'=>$this->ident,
                                'name'=>$file['name'],
                                'path'=>$path, 
                                'size'=>$file['size'], 
                                'time'=>date('d/m/Y H:i:s')
                            );
                        }
                    }
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : la taille dépasse le maximum indiqué par upload_max_filesize.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : la taille dépasse la valeur MAX_FILE_SIZE du formulaire.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : le fichier n'a été que partiellement téléchargé.";
                    break;
                case UPLOAD_ERR_NO_FILE: // le input file est vide
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:                    
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : erreur de configuration, un dossier temporaire est manquant.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:                    
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : Échec de l'écriture du fichier sur le disque.";
                    break;
                default:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : erreur non gérée : '".$file['error']."'.";
            } 
        }
    }
    
    /**
     * Supprime un fichier de la liste
     */
    private function delete($index)
    {
        if (User::hasAccess('AdminBase,cli'))
            $list=$this->adminFiles;
        else
            $list=$this->files;
        
        // Récupère l'ident du membre qui a chargé le fichier
        $ident=$list[$index]['ident'];

        // Récupère la liste de l'ident
        $files=unserialize(file_get_contents(Runtime::$root . self::dataPath . $ident . '/' . self::fileList));
    
        // Supprime le fichier du répertoire
        unlink ($list[$index]['path']);

        // Supprime le fichier de la liste et renumérote la liste
        unset($files[$list[$index]['index']]);
        $files=array_values($files);
        foreach($files as $index=>&$value)
            $value['index']=$index;
        
        // Enregistre la liste
        file_put_contents(Runtime::$root . self::dataPath . $ident . '/' . self::fileList, serialize($files));
        
        // Initialise les listes de fichiers (pour membres et administrateurs)
        $this->files=$files;
        $this->adminFiles=$this->makeList();
    }

    /**
     * Construit la liste des fichiers chargés, à afficher
     */
    private function makeList()
    {
        if (User::hasAccess('AdminBase,cli'))
        {
            $files=array();
            $dirImport=Runtime::$root . self::dataPath;
            $dirs=scandir($dirImport);
            foreach ($dirs as $key=>$value)
            {
                if ($value!='.' && $value!='..')
                {
                    $path=$dirImport . $value. '/' .self::fileList;
                    if (is_dir($dirImport . $value))
                    {
                        if (file_exists($path))                          
                            $files=array_merge($files, unserialize(file_get_contents($path)));
                    }
                }
            }
        }
        else
        {
            $files=$this->files;
        }

        return $files;
    }
    
    /**
     * Importe les fichiers
     */
    public function actionImportFiles()
    {
        global $files;
        
        // Verrouille la base
        // TODO : Vérouiller la base
        
        $files=$this->adminFiles=$this->makeList();

        // 1. Ajout des notices
        
        // Ouvre la sélection
        if (! $this->openSelection('*', false))
        	return;

        // Initialise le compteur de fichiers
        $nb=1;
        
        // Initialise le nombre total de notices
        $nbreftotal=0;
        
        // Parcourt la liste des fichiers
        foreach ($files as $key=>$file)
        {
            TaskManager::progress($nb.'. Import du fichier ' . $file['name'], filesize($file['path']));
           
            // Ouvre le fichier
            $f=fopen($file['path'],'r');
            if ($f === false)
                throw new Exception('Impossible d\'ouvrir le fichier '. $file['name']);
            
            // Lit la première ligne et récupère le nom des champs
            $fields=fgetcsv($f, 0, "\t", '"');
            
            // Compte le nombre de champs
            $nbFields=count($fields);

            // Lit le fichier et met à jour la base
            $nbref=0;
            while (($data=fgetcsv($f, 0, "\t", '"')) !== false)
            {
                // Ignore les lignes vides
                $h=array_filter($data);
                if (count($h)==0) continue;

                // Initialise le nombre de champ de la notice
                $nbRefFields=0;
                
                Taskmanager::progress(ftell($f));
                usleep(200);

                // Ajoute la notice
                $this->selection->addRecord();
                
                foreach ($data as $i=>$v)
                {
                    // Ignore les tabulations situées après le dernier champ
                    $nbRefFields++;
                    if ($nbRefFields>$nbFields) break;
                    
                    $fieldname=$this->map[trim($fields[$i])];
                    $v=trim(str_replace('""', '"', $v));
                    /*
                    // TODO : traitement pour textes officiels à supprimer
                    // Traitement pour les textes officiels
                    if ($fieldname=='LienAnne') continue;
                    
                    // Transformation des dates DATETEXT et DATEPUB de JJ/MM/AAAA en AAAA-MM-JJ
                    if ($fieldname == 'DateText' || $fieldname == 'DatePub')
                        $v=preg_replace('~(\d{2})/(\d{2})/(\d{4})~', '${3}-${2}-${1}', $v);
                    
                    // Concaténation des champs Annexe et LienAnne
                    // Annexe1 <url1>/Annexe2 <url2>
                    if ($fieldname=='Annexe')
                    {
                        $lienAnne=$data[array_search('LIENANNE', $fields)];
                        if ($lienAnne)
                            $v.=($v) ? ' <'.$lienAnne.'>' : '<'.$lienAnne.'>';

                    }
                    */
                    
                    $this->selection[$fieldname]=$v;
                }
                                
                // Initialise les champs Creation et LastUpdate
                // On passe par une variable intermédiaire car le 2e arguement de setfield
                // doit être passé par référence
                $d=date('Ymd');
                $this->selection['Creation']=$d;
                $this->selection['LastUpdate']=$d;
                
                // Initialise les champs FinSaisie et Valide
                // On passe par des variables intermédiaires car le 2e arguement de setfield
                // doit être passé par référence
                $v=true;
                $this->selection['FinSaisie']=$v;  // La saisie des notices est terminée
                $v=false;
                $this->selection['Valide']=$v;      // Les notices importées ne sont pas validées
                
                $this->selection->saveRecord();

                $nbref++;
            }
            
            TaskManager::progress('Fichier ' . $file['name'].' : '.$nbref.' notices intégrées');
            
            // Ferme le fichier
            fclose($f);

            // Supprime le fichier de la liste et enregistre la liste
            $this->delete($key);
            
            $files=$this->adminFiles=$this->makeList();
            
            // Met à jour les compteurs
            $nb++;
            $nbreftotal=$nbreftotal+$nbref;
        }
        
        // Ferme la base
        unset($this->selection);

        // 2. Tri de la base
        TaskManager::progress('Chargement terminé : '. $nbreftotal.' notices intégrées dans la base, démarrage du tri');

        Routing::dispatch('/base/sortdb'); // TODO : workaround       
        
//        $id=TaskManager::addTask('/base/sort', 0, null, 'Tri de la base');
////        Runtime::redirect('/taskmanager/taskstatus?id='.$id);
//        echo "Lancement d'une tâche pour trier la base obtenue...<br />";
//        echo '<a href="?id='.$id.'">Voir le tri</a>';
//
//        // Dévérouille la base
//        // TODO : Dévérouiller la base si pas déjà fait
          TaskManager::progress('Import terminé');
    }
    
    /**
     * Vérifie que le fichier chargé est valide :
     *  - fichier non vide
     *  - fichier tabulé
     *  - la première ligne contient uniquement des noms de champs
     *  - chaque ligne contient autant de tabulations que la 1ère ligne
     */
    private function isValid($path, & $error="")
    {
        // Vérifie que le fichier n'est pas vide
        if (filesize($path) == 0)
        {
            $error='Le fichier est vide (zéro octets)';
            return false;
        }
        // Ouvre le fichier
        $f=fopen($path,'r');

        // Vérifie que c'est un fichier tabulé
        $fields=fgetcsv($f, 0, "\t", '"');
        if (! is_array($fields) || count($fields) < 2)
        {
            $error='La première ligne du fichier ne contient pas les noms de champs ou ne contient qu\'un seul nom';
            return false;
        }
        // Vérifie que la première ligne contient les noms de champs
        $fields=array_flip($fields);
        if (count($t=array_diff_key($fields, $this->map)) > 0)
        {
            //$error=print_r($t,true);
            $error="champ(s) " . implode(', ', array_keys($t)) . " non géré(s)";
            
            return false;
        }
        return true; 
    } 
    
    // FIN IMPORT DE NOTICES

    public function actionValidateAll()
    {
        // Ouvre la sélection
        $selection=self::openDatabase('Valide=faux', false);
        if (is_null($selection)) return;

        // Vérifie qu'on a des réponses
        if ($selection->count==0)
        {
            echo 'Aucune notice à valider';
            return;
        }
        echo '<p>', $selection->count, ' notices à valider</p>';
        echo '<ul>';
        while (! $selection->eof())
        {
            echo '<li>Validation de la notice ', $selection->field('ref'), '</li>';
            $selection->edit();
            $value=true;
            $selection->setField('Valide',$value);
            $selection->update();
            
            $selection->moveNext();
        }
        echo '</ul>';
        $selection=null;
    }    
    
    public function actionFinSaisieTrue()
    {
        // Ouvre la sélection
        $selection=self::openDatabase('FinSaisie=faux', false);
        if (is_null($selection)) return;

        // Vérifie qu'on a des réponses
        if ($selection->count==0)
        {
            echo 'Aucune notice en cours de saisie';
            return;
        }
        echo '<p>', $selection->count, ' notices en cours de saisie</p>';
        echo '<ul>';
        while (! $selection->eof())
        {
            echo '<li>FinSaisie=true pour la notice ', $selection->field('ref'), '</li>';
            $selection->edit();
            $value=true;
            $selection->setField('FinSaisie',$value);
            $selection->update();
            
            $selection->moveNext();
        }
        echo '</ul>';
        $selection=null;
    }    
}
?>