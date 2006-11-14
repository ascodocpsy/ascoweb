<?php

/**
 * Module Base - Consultation de la base documentaire
 * Transfert vers BisDatabase
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR. 'Cart.php';

class Base extends Database
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
            'LIENANNE' => 'LienAnne',
            'LIEU' => 'Lieu',
            'LOC' => 'Loc',
            'MOTCLE' => 'MotCle',
            'NATTEXT' => 'NatText',
            'NOMP' => 'Nomp',
            'NOTES' => 'Notes',
            'NUM' => 'Num',
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
        Utils::defaultExtension($source, '.txt');
                        
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

    /**
     * Lance une recherche si une équation peut être construite à partir des 
     * paramètres passés et affiche les notices obtenues en utilisant le template 
     * indiqué dans la clé 'template' de la configuration.
     * Si aucun paramètre n'a été passé, redirige vers le formulaire de recherche.
     * Si erreur lors de la recherche, affiche l'erreur en utilisant le template
     * indiqué dans la clé 'errortemplate' de la configuration. 
     */
    public function actionSearch()
    {
        global $selection;

        // Construit l'équation de recherche
        $this->equation=$this->makeBisEquation();
        
        debug && Debug::log('Equation construite : %s', $this->equation); 
        
        // Si aucun paramètre de recherche n'a été passé, il faut afficher le formulaire
        // de recherche
        if (is_null($this->equation))
        {
            Runtime::redirect('searchform');
        }
        
        // Des paramètres ont été passés, mais tous sont vides et l'équation obtenue est vide
        if ($this->equation==='')
            return $this->showError('Vous n\'avez indiqué aucun critère de recherche.');
        
        // Ouvre la sélection
        $selection=self::openDatabase($this->equation);
        if (is_null($selection)) return;
        
        // Si on n'a aucune réponse, erreur
        if ($selection->count == 0)
            //return $this->showError("Aucune réponse. Equation : $this->equation");
            return $this->showError("Aucun document ne correspond à la requête : $this->equation", 'noanswertemplate');

        // Si on n'a qu'une seule réponse, affiche la notice complète
        if ($selection->count == 1)
            Runtime::redirect('show?ref='.$selection->field(1));

        // Détermine le template à utiliser
        if (! $template=$this->getTemplate('template'))
            throw new Exception('Le template à utiliser n\'a pas été indiqué');
        
        // Détermine le callback à utiliser
        $callback=$this->getCallback();

        // Exécute le template
        Template::run
        (
            $template,  
            array($this, $callback),
            'Template::selectionCallback'
        );
    }

    public function getField($name)
    {
        global $selection;
        
        switch ($name)
        {
            case 'array.key':
                // Panier de notices
                global $key;
                return urlencode($key);
            
            case 'nbref':
                // Panier de notices
                global $value;
                return count($value);
                
            case 'equation': 
                return $this->equation . '<br />Réponses : ' . $selection->count;

            // TODO : voir si error utilisé 
            case 'error':
                return $this->error;

            case 'template':
                // Initialise le nom du template 
                if (User::hasAccess('EditBase,AdminBase')) // TODO : SF : le template admin n'existe pas
                    $tpl='member';
                else
                    $tpl='public';

                // Même template pour les types Thèse et Mémoire
                $type=(strtolower($selection->field('Type')) == 'thèse') ? 'mémoire' : strtolower($selection->field('Type'));

                // Définit le template en fonction du nombre de réponses
                // TODO : problème pour le panier. On a show ou list suivant le nombre de notices dans le panier
                $tpl=($selection->count==1) ? "templates/${tpl}_show_$type.yaml" : "templates/${tpl}_list_$type.yaml";
                
                // Charge le template
                Template::run
                (
                    $tpl, 
                    array($this, 'getField'),
                    'Template::selectionCallback'
                );
                return '';

            case 'loadtemplate':
                // Récupère le type de document, à partir des paramètres ou à partir de la base
                if (! $type=strtolower(Utils::get($_REQUEST['Type'], ''))) $type=strtolower($selection->field('Type'));
                
                // Même template pour les type Thèse et Mémoire
                if ($type == 'thèse') $type= 'mémoire';

                Template::run
                (
                    "templates/load/load_$type.yaml", 
                    'Template::selectionCallback',
                    'Template::emptyCallback',
                    'Template::requestCallback'
                );
                return '';
                            
            case 'Tit':
                // Lien vers texte intégral
                if (($tit=$selection->field($name)) && ($lien=$selection->field('Lien')))
                    return $this->link($tit, $lien, 'Accéder au texte intégral (ouverture dans une nouvelle fenêtre)', true);
                return;
            
            case 'Annexe':
                // TODO : revoir : ne marche pas avec Titre de l'annexe1 <http://www.lien.fr >/Titre de l'annexe2/< http://www.lien2.fr>
                // Lien vers texte intégral
                // Syntaxe du champ :
                // Titre de l'annexe1 <http://www.lien.fr>/Titre de l'annexe2/<http://www.lien2.fr>
                $value='';
                $h=$selection->field($name);
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
                if (! $h=$selection->field($name)) return '';
                
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
                if (! $h=$selection->field($name)) return ;
                
                if (stripos($h, 'p.') === false && stripos($h, 'pagination') === false)
                    return trim($h).' p.';
                return;
            
            case 'PageEdit':
                if (! $page=$selection->field('Page')) return;
                
                if ($selection->field('Type') == 'Rapport')
                {
                    if ($h=$selection->field('Lieu').$selection->field('Edit').$selection->field('Reed'))
                        return (stripos($page, 'p.') === false && stripos($page, 'pagination') === false) ? trim($page).' p.' : $page;
                }
                return '';    
            
            case 'PageRev':
                if (! $page=$selection->field('Page')) return;
                
                if ($selection->field('Type') == 'Rapport')
                {
                    if ($h=$selection->field('Rev').$selection->field('Vol').$selection->field('Num'))
                        return (stripos($page, 'p.') === false && stripos($page, 'pagination') === false) ? trim($page).' p.' : $page;
                }
                return '';    
                
            case 'MotCle':
            case 'Nomp':
            case 'CanDes':
            case 'Theme':
                if (! $h=$selection->field($name)) return;
    
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
                if (! $h=trim($selection->field($name))) return '';
                $lien='search?rev='. urlencode($h);
                return $this->link($h, $lien, 'Notices du périodique '.$h);
                      
            case 'DateText':
            case 'DatePub':
            case 'DateVali':
            case 'Creation':
            case 'LastUpdate':
                if (! isset($selection)) return;
                // Affiche les dates AAAA-MM-JJ et AAAAMMJJ sous la forme JJ/MM/AAAA
                if (! $h=$selection->field($name)) return ;
                return preg_replace('~(\d{4})[-]?(\d{2})[-]?(\d{2})~', '${3}/${2}/${1}', $h);

            case 'Loc':
            case 'ProdFich':
                if (! $h=$selection->field($name)) return '';
                
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
                
            case 'Localisation':
               if (! $h=$selection->field('Rev')) return '';
               
               // Lien vers la fiche Périodique du titre de périodique contenu dans le champ Rev,
               // pour obtenir la localisation
               $lien='locate?rev='. urlencode($h);
               return '<a class="locate" href="' . Routing::linkFor($lien) . '" title="Localiser le périodique">&nbsp;<span>Localiser</span></a>';

            case 'Presentation':
                if (! $h=$selection->field('Rev')) return '';
                
                // Notice d'un document type Périodique
                if (Utils::convertString($selection->field('Type'))=='periodique')
                {
                    if (! $lien=$selection->field('Lien')) return '';
                    
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
                if (! $t=$selection->field($name)) return '';
                
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
                
            case 'ShowModifyBtn':
                $h=1;
                // Si la saisie de la notice n'est pas terminée, seul les membres
                // spécifiés dans le champ ProdFich peuvent modifier la notice
                if ($selection->field('FinSaisie') == 0)
                {
                    $t=split('/', $selection->field('ProdFich'));
                    if (! in_array($this->ident, $t)) $h=0;
                }
                return ($h == 1) ? true : false;
        }
    }
    
    // TODO: apparemment, jamais utilisé
    public function getDates($name)
    {
        global $selection;
        
        switch ($name)
        {
            case 'Creation':
            case 'LastUpdate':
                if (! isset($selection) || ! $h=$selection->field($name)) return;
                // Affiche les dates AAAAMMJJ sous la forme JJ/MM/AAAA
                return preg_replace('~(\d{4})(\d{2})(\d{2})~', '${3}/${2}/${1}', $h);
            
            default:
                return;                 
        }
    }
   
    public function setField($name, &$value)
    {
        global $selection;
        
        switch ($name)
        {
            case 'FinSaisie':
                if (Utils::get($_REQUEST[$name], false)) $value=true; else $value=false;
                break;
            
            case 'Valide':
                if (User::hasAccess('EditBase'))
                {
                    // Si la notice est modifiée par un membre du GIP, la notice repasse
                    // en statut "à valider par un administrateur"
                    $value=false;
                }
                else
                {
                    if (Utils::get($_REQUEST[$name], false)) $value=true; else $value=false;
                }
                break;
            
            case 'Creation':
                if (! $selection->field($name))
                    $value=date('Ymd');
                else
                    $value=$selection->field($name);
                break;

            case 'LastUpdate':
                $value=date('Ymd');
                break;
        }
    }
  
    public function actionNew()
    {
        // Détermine le template à utiliser
        if (! $template=$this->getTemplate('template'))
            throw new Exception('Le template à utiliser n\'a pas été indiqué');
        
        // Détermine le callback à utiliser
        $callback=$this->getCallback();

        // Exécute le template
        Template::run
        (
            $template, 
            array($this, $callback)
        );
    }
    
    public function actionLocate()
    {
        global $selection;
               
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de périodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de périodique n'a été précisé.");
        
        // Construit l'équation de recherche
        $eq='rev="'.$rev.'" et Type=periodique';
        
        // Recherche la fiche Périodique
        $selection=self::openDatabase($eq);
        if (is_null($selection)) return;

        switch ($selection->count)
        {
            case 0:
                return $this->showError('Aucune localisation n\'est disponible pour le périodique '.$rev.'.');
            
            default:
                $revinit=$rev;
                $rev=Utils::convertString($rev);
                $selection->movefirst();
                while (! $selection->eof)
                {
                    if ($rev == Utils::convertString($selection->field('Rev')))
                    {
                        // Réouvre la sélection contenant uniquement la notice du périodique
                        $selection=self::openDatabase('REF='. $selection->field(1), true);
                        if (is_null($selection)) return;
                        
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
                            'Template::selectionCallback'
                        );
                        exit;
                    }
                    $selection->movenext();
                }
                return $this->showError('Aucune localisation n\'est disponible pour le périodique '.$revinit.'.');
        };
    }
    
    public function actionInform()
    {
        global $selection;
               
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de périodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de périodique n'a été précisé.");
        
        // Construit l'équation de recherche
        $eq='rev="'.$rev.'" et Type=periodique et Lien=ascodocpsy';
        
        // Recherche la fiche Périodique
        $selection=self::openDatabase($eq);
        if (is_null($selection)) return;

        switch ($selection->count)
        {
            case 0:
                return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$rev.'.');
            
            default:
                $revinit=$rev;
                $rev=Utils::convertString($rev);
                $selection->movefirst();
                while (! $selection->eof)
                {
                    if ($rev == Utils::convertString($selection->field('Rev')))
                    {
                        // Réouvre la sélection contenant uniquement la notice du périodique
                        $selection=self::openDatabase('REF='. $selection->field(1), true);
                        if (is_null($selection)) return;
                        
                        // Redirige vers l'URL du champ Lien (lien sur le site ascodocpsy.org)
                        Runtime::redirect($selection->field('Lien'), true);

                        exit;
                    }
                    $selection->movenext();
                }
                return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$revinit.'.');
        };
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

    // ------------------- GESTION DU PANIER -------------------
    
    private function getCart()
    {
        // Crée (et charge s'il existe déjà) le panier contenant les notices
        // sélectionnées. Si le panier est modifié, il sera automatiquement
        // enregistré à la fin de la requête en cours.
        if (! isset($this->cart))
            $this->cart=new Cart('selection');
    }
    
    public function actionShowCart()
    {
        global $selection;
        
        $this->getCart();
               
        if ($this->cart->count()==0)
        {
            Template::run('templates/empty_cart.yaml');
            return;
        }

        // Charge la liste des formats d'export disponibles
        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
        global $formats; // pour que ce soit accessible dans le template 
        $formats=Config::get('formats');
        
        // Définit le template d'affichage 
        if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
            $tpl='member'; //'dm';
        else
            $tpl='public';
        $tpl.='_cart.yaml';

        // Construit l'équation de recherche
        $equation='';
        foreach ($this->cart->getItems() as $type=>$items)
        {
            foreach($items as $ref)
            {
                if ($equation) $equation.=' ou ';
                $equation.='ref='.$ref;
            }
        }
        $selection=self::openDatabase($equation);
        
        // Exécute le template
        Template::run
        (
            "templates/$tpl", 
            array($this, 'getField'),
            'Template::selectionCallback',
            array
            (
                'format'=>'commun',
                'body'=>'Les notices sélectionnées figurent dans le(s) document(s) joint(s)', // TODO: à virer, uniquement parce que le génrateur ne prends pas correctement 'value' en compte
//                'cart'=>$this->cart->getItems()
            )
        );
    }

    /**
     * Ajoute une ou plusieurs notices dans le panier
     */
    public function actionAddToCart()
    {
        // Construit l'équation de recherche
//        $this->equation=$this->makeBisEquation('btnadd');
//        debug && Debug::log('Equation construite : %s', $this->equation); 
//        
//        // Aucun paramètre n'a été passé
//        if (is_null($this->equation))
//            return $this->showError('Pour constituer votre panier, vous devez cocher les notices qui vous intéressent.');
//        
//        // Des paramètres ont été passés, mais tous sont vides et l'équation obtenue est vide
//        if ($this->equation==='')
//            return $this->showError('Pour constituer votre panier, vous devez cocher les notices qui vous intéressent. BIS');
        
        $art=Utils::get($_REQUEST['art']);

        if (is_null($art))
            return $this->showError('Pour constituer votre panier, vous devez cocher les notices qui vous intéressent.');

        $equation='';
        
        // Construit l'équation de recherche
        if (is_array($art))
        {
            foreach ($art as $value)
            {
                if ($equation) $equation.=' ou ';
                $equation.='ref='.$value;
            }
        }
        else
        {
            $equation='ref='. $art;
        }        

        // Ouvre la sélection
        $selection=self::openDatabase($equation);
        if (is_null($selection)) return;

        // Ouvre ou crée le panier général
        $this->getCart();
        $carts= & $this->cart->getItems();
        
        // Ajoute toutes les notices de la sélection
        while (! $selection->eof)
        {
            $type=$selection->field('Type');
            $ref=$selection->field('REF');

            $carts[$type][$ref]=$ref; // si $carts[$type] n'existe pas encore, il est créé 

            $selection->moveNext();
        }
        $this->actionShowCart();
    }
    
    /**
     * Supprime une ou plusieurs notices du panier
     */
    public function actionRemoveFromCart()
    {
        $art=Utils::get($_REQUEST['art']);
        
        if (is_null($art))
            return $this->showError('Vous devez cocher les notices à supprimer.');

        // Ouvre le panier général
        $this->getCart();
        $carts= & $this->cart->getItems();

        if (! is_array($art)) $art=array($art);

        // Supprime les notices du panier
        $selection=self::openDatabase('');
        foreach ($art as $value)
        {
            $selection->equation='ref='.$value;
            
            // Si la notice n'existe plus dans la base
            if ($selection->eof)
            {
                foreach ($carts as $key=>$ref)
                {
                    if (array_key_exists($value, $ref))
                        unset($carts[$key][$value]);
                }
            }
            // La notice existe dans la base
            else
                unset($carts[$selection->field('Type')][$value]);
        }

        // Affiche le panier
        $this->actionShowCart();
    }
    
    /**
     * vide le panier
     */
    public function actionClearCart()
    {
        $this->getCart();

        $this->cart->clear();
        $this->actionShowCart();
    }

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
    public function actionSort()
    {
        $start_time=microtime(true);
        
        $sort=array();
        
        // Ouvre la sélection
        // TODO : ouvrir la base en mode exclusif
        $selection=self::openDatabase('*', true);
        if (is_null($selection)) return;
        
        // Récupère la clé de tri
        $sortKey=Config::get('sortkey');
                
        // Vérrouille la base
        // TODO : Faire le lock sur la base si pas déjà fait
        
        // Crée la clé de tri
        $key=$this->createSortKey($sortKey);
        
        // Parcourt toute la sélection en créant les clés de tri
        TaskManager::progress('1. Calcul des clés de tri...', $selection->count);
        $i=0;
        $selection->movefirst();
        while (! $selection->eof())
        {
            $sort[$selection->field(1)]=$this->getKey($key, $selection);
            TaskManager::progress(++$i, 'Notice ' . $selection->field('REF'));
            $selection->movenext();
        }

        // Trie les clés
        TaskManager::progress('2. Tri des clés...');
        asort($sort);
        
        // Crée et ouvre la base résultat
        // Pour le moment, on part d'une base vide
        // Copie la base vide vers la base résultat
        // TODO : Ecrire un createdatabase
        $database=Config::get('database');
        $dbPath=Runtime::$root . "data/db/$database.bed";
        $dbSortPath=Runtime::$root . "data/db/$database.sort";
        
        TaskManager::progress('3. Création de la base vide...');
        if (! copy(Runtime::$root . "data/db/${database}Vide.bed", $dbSortPath))
            throw new Exception('La copie de la base n\'a pas réussi.');       
        $selSort=self::openDatabase('', false, $database.'sort');
        if (is_null($selSort)) return;
        
        // Génère la base triée
        TaskManager::progress('4. Réécriture des enregistrements selon l\'ordre de tri...', count($sort));
        
        $ref=1;
        foreach ($sort as $key=>$value)
        {
            $selection->current=$key;
            $selSort->addnew();
            $selSort->setfield(1,$ref);
            for ($i=2;$i<=$selection->fieldscount;$i++)
            {
                // On passe par une variable intermédiaire car le 2e argument de
                // setfield doit être passé par référence
                $h=$selection->field($i);
                if ($h===null) $h='';
                $selSort->setfield($i, $h);
            }
            $selSort->update();
            $ref++;
            
            TaskManager::progress($ref, "Notice $ref");
        }
        echo '<p>tri réalisé en '. number_format(microtime(true) - $start_time, 2, '.', '')
             . '&nbsp;secondes</p>';
        //TaskManager::progress('Tri de la base terminé.');
       
        TaskManager::progress('5. Fermeture et flush des bases...');

        // Ferme la base non triée
        unset($selection);
        
        // Ferme la base triée
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
        $sortKey.=';Ref,6,KeyInteger,+';
        
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
     * 
     */
    private function getKey($key, $selection)
    {
        $getKey='';
        for ($i=0;$i<=count($key)-1;$i++)
        {
            // Récupère le premier champ rempli parmi la liste de champs
            for ($j=0;$j<=count($key[$i]['fieldnames'])-1;$j++)
            {
                $value=$selection->field($key[$i]['fieldnames'][$j]);
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
        // TODO : Améliorer le module d'import :
        // - Permettre de choisir les fichiers à importer (case à cocher)
        // - Avoir une case à cocher "Marquer les notices comme validées"
        // - Avoir une case à cocher "Lancer le tri après l'import"
        // - Avoir la possibilité de lancer un tri à tout moment
        
        global $files, $error;
        
        $error='';
        
        // Importe les fichiers et trie la base
        if (! is_null(Utils::get($_REQUEST['import'])))
        {
            // Vérifie qu'il y a des fichiers à importer
            if (count($this->makeList())==0)
            {
                $error .= '<li>Il n\'y a aucun fichier à importer.</li>';
                Template::run('templates/import/import.yaml','Template::varCallback');
                return;
            }
            
            // Vérifie que le gestionnaire de tâche est démarré
            if (! TaskManager::isRunning())
                throw new Exception('Le gestionnaire de tâches n\'est pas démarré.');

            // Définit le moment du lancement de l'import : maintenant ou plus tard
            $timeImport=Utils::get($_REQUEST['now']);
            if (is_null($timeImport))
                throw new Exception('Le moment du lancement de l\'import n\'a pas été défini.');

            switch ($timeImport)
            {
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
                    
                case 1: // maintenant
                    $id=TaskManager::addTask('/base/importfiles', 0, null, 'Import des fichiers de notices');
                    break;
                
                default:
                    throw new Exception('Choix non valide pour définir le moment du lancement de l\'import');
            }
            
            Runtime::redirect('/taskmanager/taskstatus?id='.$id);
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
            $this->upload();

        // Enregistre la liste
        file_put_contents(Runtime::$root . self::dataPath . $this->ident . '/' . self::fileList, serialize($this->files));

        // Initialise $files avec la liste complète des fichiers, pour affichage
        // Pour les administrateurs, $files contient l'ensemble des fichiers chargés sur le serveur
        $files=$this->makeList(); // HACK: les templates ne savent looper que sur des globaux
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
        Template::run('templates/import/import.yaml','Template::varCallback');
    }
    
    /**
     * Récupère les fichiers chargés uploadés
     * 
     */
    private function upload()
    {
        global $error;
        
        foreach($_FILES as $file)
        {
            switch($file['error'])
            {
                case UPLOAD_ERR_OK:
                    //$path=Runtime::$root . self::dataPath . $file['name'];
                    $path=Runtime::$root . self::dataPath . $this->ident . '/' . $file['name'];
                    if ($this->isValid($file['tmp_name'])== false)
                    {
                        $error .= "<li>Le fichier '" . $file['name'] . "' n'est pas valide.</li>";
                    }
                    else
                    {
                        if (move_uploaded_file($file['tmp_name'], $path)==false)
                            $error .= "<li>Impossible d'enregistrer le fichier '" . $file['name'] . "'.</li>";
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
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : la taille dépasse le maximum indiqué par upload_max_filesize.</li>";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : la taille dépasse la valeur MAX_FILE_SIZE du formulaire.</li>";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : le fichier n'a été que partiellement téléchargé.</li>";
                    break;
                case UPLOAD_ERR_NO_FILE: // le input file est vide
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:                    
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : erreur de configuration, un dossier temporaire est manquant.</li>";
                    break;
                case UPLOAD_ERR_CANT_WRITE:                    
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : Échec de l'écriture du fichier sur le disque.</li>";
                    break;
                default:
                    $error .= "<li>Impossible de charger le fichier '" . $file['name'] . "' : erreur non gérée : '".$file['error']."'.</li>";
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
        $selection=self::openDatabase('*', false);
        if (is_null($selection)) return;

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
                $selection->addnew();
                
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
                    $selection->setfield($fieldname, $v);
                }
                                
                // Initialise les champs Creation et LastUpdate
                // On passe par une variable intermédiaire car le 2e arguement de setfield
                // doit être passé par référence
                $d=date('Ymd');
                $selection->setfield('Creation', $d);
                $selection->setfield('LastUpdate', $d);
                
                // Initialise les champs FinSaisie et Valide
                // On passe par des variables intermédiaires car le 2e arguement de setfield
                // doit être passé par référence
                $v=true;
                $selection->setfield('FinSaisie', $v);  // La saisie des notices est terminée
                $v=false;
                $selection->setfield('Valide', $v);     // Les notices importées ne sont pas validées
                
                $selection->update();

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
        unset($selection);

        // 2. Tri de la base
        TaskManager::progress('Chargement terminé : '. $nbreftotal.' notices intégrées dans la base, démarrage du tri');

        Routing::dispatch('/base/sort'); // TODO : workaround       
        
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
    private function isValid($path)
    {
        // Vérifie que le fichier n'est pas vide
        if (filesize($path) == 0)
            return false;
        
        // Ouvre le fichier
        $f=fopen($path,'r');

        // Vérifie que c'est un fichier tabulé
        $fields=fgetcsv($f, 0, "\t", '"');
        if (! is_array($fields) || count($fields) < 2)
            return false;
        
        // Vérifie que la première ligne contient les noms de champs
        $fields=array_flip($fields);
        if (count(array_diff_key($fields, $this->map)) > 0)
            return false;
        
        return true; 
    } 

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
}
?>