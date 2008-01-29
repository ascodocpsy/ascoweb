<?php

/**
 * Module Base - Consultation de la base documentaire
 * Transfert vers BisDatabase
 */

class Base extends DatabaseModule
{
    // TODO : A la place du template 'templates/error/error.yaml' mettre en place un syst�me de message d'erreur.
    
    // TODO : Faire en sorte de r�cup�rer le s�parateur d'articles (� partir
    // du .def, de la config, ...
    // S�parateur d'articles
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
     * Identifiant de la personne connect�e
     */
    private $ident;
    
    private $tblAsco=array();
    
    public function preExecute()
    {
        if (parent::preExecute()===true) return true;
     //   if ($this->action=='exportCart')
     //       $this->actionExportCart(true);
        if (Utils::isAjax())
        {
//            $this->setLayout('none');
//            Config::set('debug', false);
//            Config::set('showdebug', false);
//            header('Content-Type: text/html; charset=ISO-8859-1'); // TODO : avoir une rubrique php dans general.yaml permettant de "forcer" les options de php.ini
            if ($this->action==='search') Config::set('template','templates/answers.html');
        }
         
        if ($this->action=='exportCartByType')
            $this->actionExportCartByType(true);

        // R�cup�re l'identifiant
        $this->ident=strtolower(User::get('login'));

        // Charge la table de correspondances entre le num�ro d'un centre (ascoX)
        // et son num�ro de l'article sur le site Ascodocpsy
        $this->tblAsco=$this->loadTable('annuairegip');
        
        // TODO : je ne veux pas de �a ! (le jour o� ils ajoutent un champ, faut mettre � jour le script)
        // prendre tous les champs de la base un par un et les mettre en maju, on devrait avoir la m�me chose
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
 * Transforme un fichier texte tabul� en tableau
 * Le fichier texte est de la forme suivante :
 * 1�re ligne : Ent�te1[tab]Ent�te2
 * lignes suivante : Valeur1[tab]Valeur2
 */
    private function loadTable($source)
    {
        $t=array();
        
        // Ajoute l'extension par d�faut s'il y a lieu
        $source=Utils::defaultExtension($source, '.txt');
                        
        // D�termine le path exact de la table
        $h=Utils::searchFile
        (
            $source,                                    // On recherche la table :
            //dirname(self::$stateStack[1]['template']),  // dans le r�pertoire du script appellant
            Runtime::$root . 'tables',                  // dans le r�pertoire 'tables' de l'application
            Runtime::$fabRoot . 'tables'                // dans le r�pertoire 'tables du framework
        );
        if (! $h)
            throw new Exception("Table non trouv�e : '$source'");
        
        $file=@fopen($h, 'r');
        if ($file === false)
            throw new Exception('Impossible d\'ouvrir le fichier '. $source);

        // Lit la ligne d'ent�te
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
                // Ce champ contient le ou les titres des annexes sous la forme :
                // Titre annexe 1/Titre annexe 2/Titre annexe 3
                // Il est coupl� avec le champ LienAnne :
                // http://url.annexe1.fr ; http://url.annexe2.fr ; http://url.annexe3.fr
                // Si LienAnne renseign� alors, il y a toujours :
                //  - un lien et un seul par annexe
                //  - autant de titres d'annexe que de liens
                 
                if ($this->selection[$name]=='' ) return '';
                if (!$annexe=(array)$this->selection[$name]) return '';   // Cas o� $this->selection[$name] est null
                
                // Si pas de lien, on affiche uniquement les titres des annexes
                if ($this->selection['LienAnne']=='') return implode(self::SEPARATOR, $annexe);
                if (!$lien=(array)$this->selection['LienAnne']) return implode(self::SEPARATOR, $annexe);
                
                $value='';
                for ($i=0;$i<=count($annexe)-1;$i++)
                {
                    if ($value) $value.=self::SEPARATOR;
                    $value.=$this->link($annexe[$i], $lien[$i], 'Acc�der au texte int�gral (ouverture dans une nouvelle fen�tre)', true);
                }
                return $value;

            case 'Aut':
            	if ($this->selection[$name]=='' ) return '';
                if (!$t=(array)$this->selection[$name]) return '';  // Cas o� $this->selection[$name] est null

                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    if ($aut=$this->author($h))
                    {
                        // Lien sur l'auteur : lance une nouvelle recherche
                        $lien='search?aut='. urlencode('"'.$aut.'"');
                        $h=$this->link($h, $lien, 'Bibliographie de '.$h);
                    }
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
            
            case 'Edit':
            case 'Lieu':
            	if ($this->selection[$name]=='' ) return '';
                if (!$t=(array)$this->selection[$name]) return '';
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
                if ($this->selection[$name]=='' ) return '';
                if (! $t=(array)$this->selection[$name]) return '';
                
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    $lien='search?motscles='. urlencode('['.$h.']');
                    $h=$this->link($h, $lien, 'Notices index�es au descripteur '.$h);
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Rev':
                // Lien vers une nouvelle recherche "notices de ce p�riodique"
                if (! $h=trim($this->selection[$name])) return '';
                $lien='search?rev='. urlencode('['.Utils::convertString($h).']');
                return $this->link($h, $lien, 'Notices du p�riodique '.$h);
                      
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
            	if ($this->selection[$name]=='' ) return '';
                if (! $t=(array)$this->selection[$name]) return '';

                foreach ($t as $key=>$h)
                {
                    if ($h=trim($h))
                    {
                        // 1. Lien sur le nom du centre : lance une nouvelle recherche
                        $lien1=$this->link
                        (
                            $h,
                            'search?'.strtolower($name).'='. urlencode($h),
                            ($name=='Loc') ? 'Documents localis�s au centre '.$h : 'Notices produites par le centre '.$h
                        );
                        $t[$key]=$lien1;
                        
                        // 2. Lien vers la pr�sentation du centre                    
                        // Recherche le num�ro de l'article correspondant � l'URL de la fiche de pr�sentation du centre
                        // et construit le lien
                        if (isset ($this->tblAsco[$h]))
                        {                       
                            $lien2=$this->link
                            (
                                '&nbsp;<span>Pr�sentation du centre '.$h.'</span>',
                                Config::get('urlarticle').$this->tblAsco[$h], 
                                'Pr�sentation du centre '.$h.' (ouverture dans une nouvelle fen�tre)',
                                true,
                                'inform'
                             );
                             $t[$key].='&nbsp;'.$lien2;
                        }                        
                    }
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Presentation':
                if (! $h=$this->selection['Rev']) return '';
                
                // Notice d'un document type P�riodique
                if (Utils::convertString($this->selection['Type'])=='periodique')
                {
                    if (! $lien=$this->selection['Lien']) return '';
                    
                    if (strpos(strtolower($lien), 'ascodocpsy') !== false)
                        $title='Pr�sentation du p�riodique (ouverture dans une nouvelle fen�tre)';
                    else
                        $title='Acc�der au texte int�gral (ouverture dans une nouvelle fen�tre)';

                    // Lien vers la page de pr�sentation de la revue ou vers le texte int�gral
                    // avec ouverture dans une nouvelle fen�tre
                    return $this->link('&nbsp;<span>Pr�sentation</span>', $lien, $title, true, 'inform');
                }
                else
                {
                    // Lien vers la page de pr�sentation de la revue sur le site d'Ascodocpsy
                    $lien='inform?rev='. urlencode(Utils::convertString($h,'lower'));
                    return $this->link('&nbsp;<span>Pr�sentation</span>', $lien, 'Pr�sentation du p�riodique (ouverture dans une nouvelle fen�tre)', true, 'inform');
                }

            case 'EtatCol':
            	if ($this->selection[$name]=='' ) return '';
                if (! $t=(array)$this->selection[$name]) return '';
                
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    
                    // Extrait le num�ro du centre asco (ex : "08 : 1996-2002(lac.)")
                    $length= (strpos($h, ':') === false) ? strlen($h) : strpos($h, ':');
                    $savCentre=trim(substr($h, 0, $length));    // 08
                    
                    // Construit le nom du centre
                    $centre= (substr($savCentre, 0, 1) == '0') ? 'asco'.substr($savCentre, 1) : 'asco'.$savCentre;  // asco8
                    
                    // Recherche l'URL de la fiche de pr�sentation du centre correspondant au num�ro asco
                    if (isset ($this->tblAsco[$centre]))
                    {
                        $lien=Config::get('urlarticle').$this->tblAsco[$centre];
                        $savCentre=$this->link($savCentre, $lien, 'Pr�sentation du centre '.$centre);
                        $t[$key]=$savCentre.' '.substr($h, $length);
                    }
                }
                return implode('<br />', $t);
                
            // utilis� pour afficher une erreur dans les templates d'erreurs �ventuellement
            // indiqu�s dans la configuration
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

    
    // Utilis� uniquement pour actionLoad et actionLoadFull (cf config.yaml)
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
			// Champs articles : on transforme le contenu en tableau
        	case 'Aut':
        	case 'MotCle':
        	case 'Nomp':
        	case 'CanDes':
        	case 'Edit':
        	case 'Lieu':
        	case 'EtatCol':
        	case 'Loc':
        	case 'ProdFich':
            case 'Annexe':
            case 'LienAnne':
                // Si le champ n'est pas renseign�, alors l'action Save renvoie la valeur NULL 
                if (is_null($value)) break;
                if ( $value==='' || (is_array($value) && count($value)===0) )
                { 
                    $value=null;
                    break;
                };              
                
                // Transforme en tableau
                if (! is_array($value))
                {
                    // Le s�parateur pour le champ LienAnne est ' ; ' et non pas le slash car LienAnne contient des URL
                    $sep=($name=='LienAnne') ? ';' : trim(self::SEPARATOR);
                    $value=explode($sep,$value);
                }
                
                // Supprime les cha�nes vides
                $t=array_map('trim',$value);
                $value=array();
                foreach($t as $v)
                    if ($v!=='') $value[]=$v;

                break;
        	    
        	case 'Statut':
        	    // On garde le statut actuel de la notice s'il n'est pas transmis lors de l'enregistrement de la notice
        	    if (is_null($value) && $this->selection[$name]) return false;
        	    break;
        	    
            case 'Creation':
                if ($this->selection[$name]) return false;
                $value=date('Ymd');
                break;

            case 'LastUpdate':
                $value=date('Ymd');
                break;

            case 'LastAuthor':
                $value=$this->ident;
                break;

            default:
            	if ($value==='' || (is_array($value) && count($value)===0))
            		$value=null;
        }
        return true;
    }
   
    /**
     * D�termine les utilisateurs qui ont le droit de modifier une notice
     */
 	public function hasEditRight()
 	{
 		// Administrateurs : peuvent modifier les notices quel que soit leur statut
 		if (User::hasAccess('AdminBase')) return true;
 		
		// Membres du GIP
 		if (User::hasAccess('EditBase'))
 		{
			// Si la notice n'a pas �t� valid�e par un administrateur, seul les
			// membres sp�cifi�s dans le champ ProdFich peuvent modifier la notice
	        if ($this->selection['Statut'] != 'valide')
	            return (in_array($this->ident, (array)$this->selection['ProdFich'])) ? true : false;
 
			// Si la notice a �t� valid�e, tous les membres peuvent modifier la notice
			return true;
 		}

 		// Grand public : ne peuvent pas modifier les notices
 		return false;
 	}
 
    public function actionLocate()
    {                       
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de p�riodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de p�riodique n'a �t� pr�cis�.");
        
        // Construit l'�quation de recherche
        $eq='rev=['.$rev.'] et Type=periodique';
        
        // Ouvre la base de donn�es
        $this->openDatabase();

		// Fiche P�riodique inexistante
        if (! $this->select($eq))
        	return $this->showError('Aucune localisation n\'est disponible pour le p�riodique '.$rev.'.');
        
        // Fiche Priodique existante 
        
        // D�termine le template � utiliser
        if (! $template=$this->getTemplate())
            throw new Exception('Le template � utiliser n\'a pas �t� indiqu�');

        // D�termine le callback � utiliser
        $callback=$this->getCallback();

        // Ex�cute le template
        Template::run
        (
            $template,  
            array($this, $callback),
            $this->selection->record,
            array('selection',$this->selection)  
        );
    }

    public function actionInform()
    {              
        $rev=Utils::get($_REQUEST['rev']);
        
        // Si pas de nom de p�riodique
        if (is_null($rev))
            throw new Exception("Appel incorrect : aucun nom de p�riodique n'a �t� pr�cis�.");
        
        // Construit l'�quation de recherche
        $eq='rev=['.$rev.'] et Type=periodique';
        
        // Ouvre la base de donn�es
        $this->openDatabase();

		// Fiche P�riodique inexistante
        if (! $this->select($eq))
            return $this->showError('Aucune page de pr�sentation n\'est disponible sur le site www.ascodocpsy.org, pour le p�riodique '.$rev.'.');
        
        // Erreur si plusieurs notices pour le p�riodique
        if ($this->selection->count() >= 2 )
        	return $this->showError('Il existe plusieurs notices descriptives pour le p�riodique '.$rev.'.');
        
        // Fiche P�riodique existante mais le champ Lien ne contient pas www.ascodocpsy.org
        if (stripos($this->selection['Lien'], 'ascodocpsy') === false)
        	return $this->showError('Aucune page de pr�sentation n\'est disponible sur le site www.ascodocpsy.org, pour le p�riodique '.$rev.'.');
        
        // Fiche P�riodique existante, redirige vers l'URL du champ Lien (lien sur le site ascodocpsy.org)
        Runtime::redirect($this->selection['Lien'], true);
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
                'pr�f.',
                'trad.'
            ),
            null,
            $value));
        
        $value=preg_replace('~(.+)(?:(n�|n�e|�p.)[ ].+)~','$1', $value);
        
        return trim($value);
        
//        return trim(preg_replace(
//            array
//            (
//                '~(.+)(?:(n�|n�e)[ ].+)~',
//                '/[s.n.]/',
//                '/collectif/i',
//                '/collab/i',
//                '/coord/i',
//                '/dir/i',
//                '/ed/i',
//                '/ill/i',
//                '/pr�f/i',
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
     * Affiche le formulaire permettant de choisir le type du document � cr�er
     * Surcharge l'action new de la classe DatabaseModule
     */    
    public function actionNew()
    {       
        // si type non renseign�, affiche le formulaire pour le choisir
        if (is_null(Utils::get($_REQUEST['Type'])))
        {
            Template::run
            (
                'templates/chooseType.html'
            );
        }
        else    // sinon, appelle l'action par d�faut
        {
            parent::actionNew();
        }
    }
     
     
     /**
      * Callback qui retourne une cha�ne vide pour chaque champ de la nouvelle notice � cr�er
      */
     public function emptyString($name)
     {
        if($name==='Type')
            return Utils::get($_REQUEST['Type']);
        else
            return '';
     }

    // ------------------- GESTION DU PANIER -------------------
   
//    private function exportCart($type, $format)
//    {
//        global $selection;
//     
//        if (is_null($type))
//            throw new Exception('Le type de document n\'a pas �t� indiqu�.');
//
//        if (is_null($format))
//            throw new Exception('Le format d\'export n\'a pas �t� indiqu�.');
//
//        // R�cup�re le panier du type $type
//        $this->getCart();
//        $carts=$this->cart->getItems();
//        $cart=$carts[$type];
//
//        // Construit l'�quation de recherche
//        $equation='';
//        foreach ($cart as $ref)
//        {
//            if ($equation) $equation.=' ou ';
//            $equation.='ref='.$ref;
//        }
//        $selection=self::openDatabase($equation);
//
//        // G�n�re l'export
//        $template=$this->path . 'templates/export/'.$format;
//        ob_start();
//        Template::run
//        (
//            $template,
//            'Template::selectionCallback'
//        );
//        $data=ob_get_clean();
//        return $data;
//    }

//    // D�chargement des notices par type de document
//    public function actionExportCartByType($now=false)
//    {
//        if (! $now) return; // preExecute nous appelle avec now=true, on bosse, quand le framework nous appelle, rien � faire 
//        // tout est fait dans preExecute
//
//        // R�cup�re le format d'export
//        $format=Utils::get($_REQUEST['format']);
//        if (is_null($format))
//            // TODO : le message d'erreur s'affiche en premier sur la page html
//            return $this->showError('Le format d\'export n\'a pas �t� indiqu�.');
//
//        // Charge la liste des formats d'export disponibles
//        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
//        if (! $format=Config::get("formats.$format"))
//            throw new Exception('Format incorrect');
//
////        $template=$this->path . 'templates/export/'.$format['template'];
////        if (! file_exists($template))
////            throw new Exception('Le fichier contenant les diff�rents formats n\'existe pas');
//
//        global $cart;
//        $this->getCart();
//        $cart=$this->cart->getItems();
//
//        $data='';
//        // R�cup�re le type de document
//        $type=Utils::get($_REQUEST['type']);
//
//        if (is_null($type))
//            return $this->showError('Le type du document n\'a pas �t� indiqu�.');
//
//        if (! isset($cart[$type]))
//            return $this->showError('Le panier ne contient aucun document du type indiqu�.');
//        
//        // R�cup�re le template
//        if (is_array($format['template']))
//        {
//            if (isset($format['template'][$type]))
//                $template=$format['template'][$type];              
//            elseif (isset($format['template']['default']))
//                $template=$format['template']['default'];
//        }
//        else
//            $template=$format['template'];
//
//        // R�cup�re le nom du fichier d'export
//        if (is_array($format['filename']))
//        {
//            if (isset($format['filename'][$type]))
//                $filename=$format['filename'][$type];              
//            elseif (isset($format['filename']['default']))
//                $filename=$format['filename']['default'];
//        }
//        else
//            $filename=$format['filename'];
//
//        if (isset($format['layout']))
//            $this->setLayout($format['layout']);
//        else
//            $this->setLayout($format['none']);
//
//        if (isset($format['content-type']))
//            header('content-type: ' . $format['content-type']);
//
//        header
//        (
//            'content-disposition: attachment; filename="' 
//            . (isset($filename) ? $filename : "notices{$type}.txt")
//            . '"'
//        );
//            
//        // G�n�re le contenu du fichier
//        $data=$this->exportCart($type, $template);              
//
//        echo $data;
//    }
    
    public function actionExportByType()
    {
        // Ouvre la base de donn�es (le nouveau makeEquation en a besoin)
        $this->openDatabase();

        // D�termine la recherche � ex�cuter        
        $this->equation=$this->selection->makeEquation(Utils::isGet() ? $_GET : $_POST);

        // Pas d'�quation : erreur
        if (is_null($this->equation) || $this->equation==='')
            return $this->showError('Aucun crit�re de recherche indiqu�');

        // Lance la recherche, si aucune r�ponse, erreur
        if (! $this->select($this->equation, array('_sort'=>'+', '_start'=>0, '_max'=>1000000)))
            return $this->showNoAnswer("La requ�te $this->equation n'a donn� aucune r�ponse.");
    	
        // IDEA: on pourrait utiliser les "collapse key" de xapian pour faire la m�me chose
        // de fa�on beaucoup plus efficace (collapser sur type : une seule r�ponse par type de
        // document, get_collapse_count() donne une estimation du nombre de documents pour chaque
        // type)
        // A voir �galement : utiliser un MatchSpy (xapian 1.0.3)
//        echo "Equation initiale : $this->equation<br />";
        $lastCount=$this->selection->count();
//        echo "Nombre total de notices : $lastCount<hr />";

        $equation=$baseEquation='(' . $this->equation . ')';
        
        $catField='Type';
        $catIndex='Type';
        $phraseSearch=false;
        
//        $catField='Creation';
//        $catIndex='Creation';
//        $phraseSearch=false;

//        $catField='MotCle';
//        $catIndex='MotCle';
//        $phraseSearch=false;
        
//        $catField='Rev';
//        $catIndex='Rev';
//        $phraseSearch=true;
        
        $categories=array();
        for($i=0;$i<100;$i++)
        {
        	$name=$cat=$this->selection[$catField];
            if (is_array($cat)) $name=$cat=reset($cat);
            if (is_null($cat) || $cat===false || $cat==='')
            {
                $cat='@isempty';
                $name='';
            }
            elseif($phraseSearch)
                $cat="[$cat]";
            elseif(strpos($cat, ' ')!==false)
                $cat="($cat)";
//            echo "ref=",$this->selection['REF'],", $catField=$cat, titre=", $this->selection['Tit'], ", rev=", $this->selection['Rev'],"<br />";
            
            $equation.=" -$catIndex:$cat";
//            echo "equation de boucle : $equation<br />";
            $found=$this->select($equation, array('_sort'=>'+', '_start'=>0, '_max'=>1000000));

            $count=$this->selection->count();
            $diff=$lastCount-$count;
            if ($diff==0)
                throw new Exception("Impossible de cr�er des cat�gories sur le champ $catField, l'�quation $equation n'a pas diminu� le nombre de r�ponses obtenues (index sans attribut count ?)");
            if ($diff<0)
                throw new Exception("Impossible de cr�er des cat�gories sur le champ $catField, l'�quation $equation a augment� le nombre de notices obtenues (utiliser l'option phraseSearch ?)");

//            echo "R�ponses : $count<br />";
//            echo "diff�rence : ", $diff, "<br />";
            
            $categories[$name]=array('equation'=>"$catIndex:$cat AND $baseEquation", 'count'=>$lastCount-$count);
            $lastCount=$count;
//            echo "lastCount passe � : $lastCount<hr />";
//            echo "<hr />";

            if (!$found) break;
        }

//        echo "<hr />done<br />";
        ksort($categories);
//        echo "<pre>";
//        var_export($categories);
//        echo "</pre>";
        $all='';
        foreach($categories as $name=>$cat)
        {
        	$url='exportbycat?_equation='.urlencode($cat['equation']);
            echo "<li><a href='$url'>$name : $cat[count] notices</a></li>";
            $all.='&_equation='.urlencode($cat['equation']) . '&filename='.urlencode($name);
        }
        if (1 < $nbCat=count($categories))
        {
            $all=substr($all,1);
            echo "<li><a href='exportbycat?$all'>Exporter les ", $nbCat, " fichiers </a></li>";
        }
    }
    
    /*
     * Callback utilis� pour les exports Vancouver
     */
    public function exportData($name)
    {
        switch ($name)
        {
            case 'Aut':
                if ($this->selection[$name]=='' ) return '';
                if (!$value=(array)$this->selection[$name]) return '';  // Cas o� $this->selection[$name] est null
                
                // R�cup�re le format d'export
                $format=Utils::get($_REQUEST['_format']);
                
                $i=6;
                if (count($value)>$i)
                {
                    $value=array_slice($value,0,$i);
                    $value[$i]='et al.';
                    $value=implode(Config::get("formats.$format.sep"), $value);
                }
                else
                {
                    $value=implode(Config::get("formats.$format.sep"),$value).'.';
                }   
                return $value;
                
            case 'Tit':
                if (! $value=$this->selection[$name]) return '';

                // Ajoute un point � la fin du titre 
                if (strpos('.!?',substr($value,-1,1))===false) return $value.'.';

            case 'PdPf':
                if (! $value=$this->selection[$name]) return '';
                
                // S'il n'y a qu'une seule page (19) ou si la pagination n'est pas continue (1 ; 3-5),
                // on laisse tel quel
                if (strpos($value,';')!==false || strpos($value,'-')===false) return $value;
                
                list($p1,$p2)=split('-',$value,2);
                $p1=trim($p1);
                $p2=trim($p2);
                
                // Si les 2 bornes de la pagination n'ont pas le m�me nombre de chiffres,
                // (95-120), on laisse tel quel
                if (strlen($p1)<>strlen($p2)) return $value;
                
                // Transforme la pagination (ex : 303-306 en 303-6) 
                for ($i=0;$i<strlen($p1);$i++)
                {
                    if ($p1[$i]<>$p2[$i])
                    {
                        $p2=substr($p2,$i);
                        break;
                    }
                }
                return $p1.'-'.$p2;
                
            default:
                return;
        }
    }
    
   // public function actionExportCart($now=false)
//    public function actionExportCart()
//    {
//        //if (! $now) return; // preExecute nous appelle avec now=true, on bosse, quand le framework nous appelle, rien � faire 
//        // tout est fait dans preExecute
//
//        // R�cup�re l'action � effectuer
//        $cmd=Utils::get($_REQUEST['cmd']);
//        if (is_null($cmd))
//            throw new Exception("La commande n'a pas �t� indiqu�e");
//            
//        if ($cmd !='export' && $cmd!='mail')
//            throw new Exception('Commande incorrecte');
//
//        // R�cup�re le format d'export
//        $format=Utils::get($_REQUEST['format']);
//        if (is_null($format))
//            // TODO : le message d'erreur s'affiche en premier sur la page html
//            return $this->showError('Le format d\'export n\'a pas �t� indiqu�.');
//
//        // Charge la liste des formats d'export disponibles
//        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
//        if (! $format=Config::get("formats.$format"))
//            throw new Exception('Format incorrect');
//
////        $template=$this->path . 'templates/export/'.$format['template'];
////        if (! file_exists($template))
////            throw new Exception('Le fichier contenant les diff�rents formats n\'existe pas');
//
//        global $cart;
//        $this->getCart();
//        $cart=$this->cart->getItems();
//
//        switch ($cmd)
//        {
//            // Envoie les notices par mail
//            case 'mail':
//                // R�cup�re des informations pour l'envoi du mail
//                $to=Utils::get($_REQUEST['to']);
//                if (is_null($to))
//                    // TODO : le message d'erreur s'affiche en premier sur la page html
//                    return $this->showError('Le destinataire du mail n\'a pas �t� indiqu�.');
//    
//                $subject=Utils::get($_REQUEST['subject']);
//                if (is_null($subject))
//                    $subject='Notices Ascodocpsy';
//    
//                $body=Utils::get($_REQUEST['body']);
//                if (is_null($body))
//                    $body='Le fichier ci-joint contient les notices s�lectionn�es';
//                
//                $data='';
//
//                $this->setLayout('none');
//    
//                require_once(Runtime::$fabRoot.'lib/htmlMimeMail5/htmlMimeMail5.php');
//            
//                $mail = new htmlMimeMail5();
//                //TODO : changer l'adresse e-mail
//                $mail->setHeader('Content-Type', 'multipart/mixed');
//                $mail->setFrom('Site AscodocPsy <gfaure@ch-st-jean-de-dieu-lyon.fr>');
//                $mail->setSubject($subject);
//                $mail->setText($body);
//                
//                // G�n�re les fichiers attach�s
//                $this->getCart();
//                $carts=$this->cart->getItems();
//                
//                // Parcourt chaque panier
//                foreach ($carts as $type=>$cart)
//                {                
//                    // R�cup�re le template
//                    if (is_array($format['template']))
//                    {
//                        if (isset($format['template'][$type]))
//                            $template=$format['template'][$type];              
//                        elseif (isset($format['template']['default']))
//                            $template=$format['template']['default'];
//                    }
//                    else
//                        $template=$format['template'];
//    
//                    // R�cup�re le nom du fichier d'export
//                    if (is_array($format['filename']))
//                    {
//                        if (isset($format['filename'][$type]))
//                            $filename=$format['filename'][$type];              
//                        elseif (isset($format['filename']['default']))
//                            $filename=$format['filename']['default'];
//                    }
//                    else
//                        $filename=$format['filename'];
//    
//                    // G�n�re le contenu du fichier
//                    $data=$this->exportCart($type, $template);              
//    
//                    $mail->addAttachment
//                    (
//                        new stringAttachment
//                        (
//                            $data,
//                            isset($filename) ? $filename : "notices{$type}.txt",
//                            isset($format['content-type']) ? $format['content-type'] : 'text/plain'
//                        )
//                    );
//                }
//                
//                if ($mail->send( array($to)) )
//                {
//                    echo '<p>Vos notices ont �t� envoy�es � l\'adresse ', $to, '</p>';
//                    echo '<p>Retour � la <a href="javascript:history.back()"> page pr�c�dente</a>.</p>';
//                }
//                else
//                {
//                    echo "<p>Impossible d'envoyer le mail � l'adresse '$to'</p>";
//                }
//                break;
//
//            // Affiche la liste des paniers (en fonction du type de document, avec liens pour t�l�charger les notices
//            case 'export':
//                // D�finit le template d'affichage 
//                if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
//                    $tpl='member';
//                else
//                    $tpl='public';
//                $tpl.='_cart_type.yaml';
//                
//                global $cart;
//                $this->getCart();
//                $cart=$this->cart->getItems();
//
//                // Ex�cute le template
//                Template::run
//                (
//                    "templates/$tpl", 
//                    array($this, 'getField'),
//    //                'Template::selectionCallback',
//                    array
//                    (
//                        'cart'=>$this->cart->getItems(),
//                        'format'=>$_REQUEST['format']
//                    )
//                );            
//                break;
//        }
//    }           
           
    // ------------------- TRI DE LA BASE -------------------
    /**
     * Trie la base, selon la cl� de tri sortkey d�fini dans le fichier
     * de configuration.
     */
    public function actionSortDb()
    {
		// TODO : � supprimer
		set_time_limit(0);

        $start_time=microtime(true);
        
        // Ouvre la base
        $database=Config::get('database');
        if (is_null($database))
            throw new Exception('La base de donn�es � utiliser n\'a pas �t� indiqu�e dans le fichier de configuration du module');
        
        $this->selection=Database::open($database, true);

        if (! $this->selection->search('*', array('_max'=>-1)))
        	die('La base � trier ne contient aucun enregistrement.');

        // V�rrouille la base
        // TODO : Faire le lock sur la base si pas d�j� fait
        
        // Cr�e la cl� de tri
        $sortKey=$this->createSortKey(Config::get('sortkey'));
        
        // Parcourt toute la s�lection en cr�ant les cl�s de tri
        TaskManager::progress('1. Calcul des cl�s de tri...', $this->selection->count());
        $i=0;
		$sort=array();
		foreach ($this->selection as $rank=>$record)
		{
		    $sort[$record['REF']]=$this->getKey($sortKey, $record);
		    TaskManager::progress(++$i, 'Notice ' . $record['REF']);
		}

        // Trie les cl�s
        TaskManager::progress('2. Tri des cl�s...');
        asort($sort);
        
        // Cr�e et ouvre la base r�sultat
        // Pour le moment, on part d'une base vide
        // Copie la base vide vers la base r�sultat
        // TODO : Ecrire un createdatabase
        $dbPath=Runtime::$root . "data/db/$database.bed";
        $dbSortPath=Runtime::$root . "data/db/$database.sort.bed";
        
        TaskManager::progress('3. Cr�ation de la base vide...');
        if (! copy(Runtime::$root . "data/db/${database}Vide.bed", $dbSortPath))
            throw new Exception('La copie de la base n\'a pas r�ussi.');       
        
        //ascodocpsysort
        $selSort=Database::open($dbSortPath, false, 'bis');
        if (is_null($selSort))
        	throw new Exception('Impossible d\'ouvrir la base r�sultat.');
        
        // G�n�re la base tri�e
        TaskManager::progress('4. R��criture des enregistrements selon l\'ordre de tri...', count($sort));
        
        $ref=1;
        foreach ($sort as $key=>$value)
        {
            if(! $this->selection->search("REF=$key"))
            	die('ref non trouv�e');
            	
            $selSort->addRecord();
			foreach($this->selection->record as $fieldName=>$fieldValue)
			    if ($fieldName!=='REF') $selSort[$fieldName]=$fieldValue;
            $selSort->saveRecord();
            
            if ($ref==1) break;
            
            TaskManager::progress($ref, "Notice $ref");
            $ref++;
        }
        echo '<p>tri r�alis� en '. number_format(microtime(true) - $start_time, 2, '.', '')
             . '&nbsp;secondes</p>';
       
        TaskManager::progress('5. Fermeture et flush des bases...');

        // Ferme la base non tri�e
        unset($record);
        unset($this->selection->record);
        unset($this->selection);
        
        // Ferme la base tri�e
        unset($selSort->record);
        unset($selSort);
        
        // Supprime la base non tri�e
        if (! unlink($dbPath))
            throw new Exception('La base non tri�e n\'a pas pu �tre supprim�e.');
        
        // Renomme la base tri�e
        if (! rename($dbSortPath, $dbPath))
            throw new Exception('La base tri�e n\'a pas pu �tre renomm�e.');
        
        // D�v�rrouille la base
        // TODO : Faire un unlock sur la base
        TaskManager::progress('Termin�');
        
        // TODO : Faire en sorte d'avoir un lien http://xxx
        //echo '<a href="'.Routing::linkFor('/base/search').'">Interroger la nouvelle base...</a>';
    }
    
    /**
     * Cr�e un tableau contenant les cl�s utilis�es pour le tri, � partir
     * de la cha�ne pass�e en param�tre
     * 
     * @param string $sortKey cha�ne contenant les cl�s de tri, �crites sous la
     * forme Champ1:Champ2:Champ3,Longueur de la cl� (entier),Type,Ordre de tri (+ ou -);Autre cl�
     * 
     * @return array tableau contenant les cl�s de tri.
     * i => fieldnames => tableau contenant les champs utilis�s pour construire la cl�
     *                    array(0=>Champ1, 1=>Champ2, 2=>Champ3)
     *      length     => longeur de la cl� de tri
     *      type       => type � utiliser pour cr�er la cl�
     *      order      => ordre du tri : ascendant (+) ou descendant (-)
     */
    private function createSortKey($sortKey)
    {
        $keys=array();
        
        // Ajoute le champ REF comme dernier champ de la cl�
        $sortKey.=';REF,6,KeyInteger,+';
        
        // Initialise tous les champs qui composent la cl�
        $t=split(';', $sortKey);
        foreach ($t as $key=>$value)
        {
            $items=split(',', trim($value));
            
            // Extrait les noms de champs
            $keys[$key]['fieldnames']=split(':', trim($items[0]));
            
            // Extrait la longueur de cl�
            $keys[$key]['length']=trim($items[1]);
            
            // Extrait le type
            $keys[$key]['type']=trim($items[2]);
            
            // Extrait l'ordre de tri
            $keys[$key]['order']=trim($items[3]);
        }
        
        // Retourne le r�sultat
        return $keys;
    }
        
    /**
     * Cr�e la cl� de l'enregistrement en cours de la s�lection $selection
     * 
     * @param array $key tableau contenant les cl�s de tri
     * @param $selection la s�lection en cours
     * 
     * @return string la cl� de l'enregistrement en cours 
     */
    private function getKey($key, $selection)
    {
        $getKey='';
        for ($i=0;$i<=count($key)-1;$i++)
        {
            // R�cup�re le premier champ rempli parmi la liste de champs
            for ($j=0;$j<=count($key[$i]['fieldnames'])-1;$j++)
            {
                $value=$selection[$key[$i]['fieldnames'][$j]];
                if (strlen($value))
                    break;
            }

            // R�cup�re la longueur de la cl�
            $nb=$key[$i]['length'];
            
            // Construit la cl�
            switch ($key[$i]['type'])
            {
                // Prendre le champ tel quel sur n caract�res
                case 'KeyText':
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    break;

                // Idem mais ignorer la casse des caract�res
                case 'KeyTextIgnoreCase':
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    $value=Utils::convertString($value, 'lower');
                    break;
            
                // Prendre le premier article tel quel et padder sur n caract�res
                case 'KeyArticle':
                    // TODO : remplacer la cha�ne du s�parateur par la variable
                    $pt=strpos($value, trim(self::SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    break;
                    
                // Idem mais ignorer la casse des caract�res
                case 'KeyArticleIgnoreCase':
                    // TODO : remplacer la cha�ne du s�parateur par la variable
                    $pt=strpos($value, trim(self::SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');
                    $value=Utils::convertString($value, 'lower');
                    break;

                // Traiter comme un entier (padder avec des z�ros sur n caract�res)
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
                    throw new Exception('Le type du champ n\'a pas �t� pr�cis�.');
                    break;
                    // TODO : que faire par d�faut ?
            }
            
            // Si tri descendant, commute la cl�
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
         
    // affiche la liste des fichiers � importer
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
        // TODO : Am�liorer le module d'import :
        // - Permettre de choisir les fichiers � importer (case � cocher)
        // - Avoir une case � cocher "Marquer les notices comme valid�es"
        // - Avoir une case � cocher "Lancer le tri apr�s l'import"
        // - Avoir la possibilit� de lancer un tri � tout moment
        
        $errors=array();
        
        // Importe les fichiers et trie la base
        if (Utils::get($_REQUEST['import']))
        {
            // V�rifie qu'il y a des fichiers � importer
            if (count($this->makeList())==0)
            {
                $errors[]= 'Il n\'y a aucun fichier � importer.';
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
            
            // V�rifie que le gestionnaire de t�che est d�marr�
            if (! TaskManager::isRunning())
                throw new Exception('Le gestionnaire de t�ches n\'est pas d�marr�.');

            // D�finit le moment du lancement de l'import : maintenant ou plus tard
            switch (Utils::get($_REQUEST['now']))
            {
                case 1: // maintenant
                    $id=TaskManager::addTask('/base/importfiles', 0, null, 'Import des fichiers de notices');
                    Runtime::redirect('/taskmanager/taskstatus?id='.$id);
                    break;
                
                case 0: // plus tard
                    // R�cup�re la date et l'heure de lancement
                    // TODO : V�rifier que $datetime est dans le format attendu
                    $datetime = Utils::get($_REQUEST['delay'], '');
                    if (strlen(trim($datetime))==0)
                        throw new Exception('La date et l\'heure du lancement de l\'import n\'ont pas �t� indiqu�es.');
                    list($day, $month, $year, $hour, $minutes, $seconds) = split('[/: ]', $datetime);
                    
                    //checkdate
                    //^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[0-2])/20[0-9]{2}[ ]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$
                    
                    // Calcul le timestamp
                    $timestamp=mktime($hour, $minutes, $seconds, $month, $day, $year);
                    
                    $id=TaskManager::addTask('/base/importfiles', $timestamp, null, 'Import des fichiers de notices');
                    Runtime::redirect('/taskmanager/');
                    break;
                    
                default:
                    throw new Exception('Choix non valide pour d�finir le moment du lancement de l\'import');
            }
            
            return;
        }
        
        // Charge la liste des fichiers � importer
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
            // Cr�e le r�pertoire de stockage des fichiers (un r�pertoire par identifiant)
            if (! is_dir($dir)) Utils::makeDirectory($dir);
            $this->files=array();
        }
        
        // On a des fichiers upload�s -> ajoute � la liste
        if (count($_FILES)!=0)
            $this->upload($errors);

        // Enregistre la liste
        file_put_contents(Runtime::$root . self::dataPath . $this->ident . '/' . self::fileList, serialize($this->files));

        // Initialise $files avec la liste compl�te des fichiers, pour affichage
        // Pour les administrateurs, $files contient l'ensemble des fichiers charg�s sur le serveur
        $files=$this->makeList();
        if (User::hasAccess('AdminBase')) $this->adminFiles=$files;
        
        // Supprime un fichier de la liste et enregistre la liste
        // Pour les administrateurs :
        // Pour les membres : appell� avec delete=i -> supprime de la liste le i�me fichier 
        $delete=Utils::get($_REQUEST['delete']);
        if (! is_null($delete))
            $this->delete($delete);
        
        // Initialise � nouveau $files avec la liste compl�te des fichiers, pour affichage
        $files=$this->makeList(); // HACK: les templates ne savent looper que sur des globaux
        if (User::hasAccess('AdminBase')) $this->adminFiles=$files;

        // Affiche la liste et le reste
        // Si administrateur, affiche l'ensemble des listes
        Template::run('templates/import/import.html',array('files'=>$files, 'errors'=>$errors));
    }
    
    /**
     * R�cup�re les fichiers charg�s upload�s
     * 
     * @param array $errors en sortie la liste des erreurs �ventuelles rencontr�es
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
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : la taille d�passe le maximum indiqu� par upload_max_filesize.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : la taille d�passe la valeur MAX_FILE_SIZE du formulaire.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : le fichier n'a �t� que partiellement t�l�charg�.";
                    break;
                case UPLOAD_ERR_NO_FILE: // le input file est vide
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:                    
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : erreur de configuration, un dossier temporaire est manquant.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:                    
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : �chec de l'�criture du fichier sur le disque.";
                    break;
                default:
                    $errors[] = "Impossible de charger le fichier '" . $file['name'] . "' : erreur non g�r�e : '".$file['error']."'.";
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
        
        // R�cup�re l'ident du membre qui a charg� le fichier
        $ident=$list[$index]['ident'];

        // R�cup�re la liste de l'ident
        $files=unserialize(file_get_contents(Runtime::$root . self::dataPath . $ident . '/' . self::fileList));
    
        // Supprime le fichier du r�pertoire
        unlink ($list[$index]['path']);

        // Supprime le fichier de la liste et renum�rote la liste
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
     * Construit la liste des fichiers charg�s, � afficher
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
        // TODO : V�rouiller la base
        
        $files=$this->adminFiles=$this->makeList();

        // 1. Ajout des notices       

        // Ouvre la base
        $this->openDatabase(false);
        
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
            
            // Lit la premi�re ligne et r�cup�re le nom des champs
            $fields=fgetcsv($f, 0, "\t", '"');
            
            // Compte le nombre de champs
            $nbFields=count($fields);

            // Lit le fichier et met � jour la base
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
                    // Ignore les tabulations situ�es apr�s le dernier champ
                    $nbRefFields++;
                    if ($nbRefFields>$nbFields) break;
                    
                    $fieldname=$this->map[trim($fields[$i])];
                    $v=trim(str_replace('""', '"', $v));
                    
                    switch ($fieldname)                    
			        {
			        	// Champs articles : on transforme le contenu en tableau
			            case 'Aut':
			        	case 'MotCle':
			        	case 'Nomp':
			        	case 'CanDes':
			        	case 'Edit':
			        	case 'Lieu':
			        	case 'EtatCol':
			        	case 'Loc':
			        	case 'ProdFich':
			        	case 'Annexe':
			        		$v=($v=='') ? null : array_map("trim",explode(trim(self::SEPARATOR),$v));
			        		break;

			            // Champs articles : on transforme le contenu en tableau
			        	case 'LienAnne':
			        	    // Pour le champ LienAnne (Adresses Internet des annexes),
			        	    // le s�parateur d'articles est le ' ; '
			        	    $v=($v=='') ? null : array_map("trim",explode(' ; ',$v));
			        	    break;

			        	default:
			        	    if ($v=='') $v=null;
			        }
                    
                    $this->selection[$fieldname]=$v;
                }
                
                // Initialisation des champs de gestion
                // Dates de cr�ation et de derni�re modification
                $this->selection['Creation']=$this->selection['LastUpdate']=date('Ymd');
                
                // Statut de la notice
                // Les notices import�es sont � valider par un administrateur
                $this->selection['Statut']='avalider';
                 
                // Enregistre la notice
                $this->selection->saveRecord();
                $nbref++;
            }
            
            TaskManager::progress('Fichier ' . $file['name'].' : '.$nbref.' notices int�gr�es');
            
            // Ferme le fichier
            fclose($f);

            // Supprime le fichier de la liste et enregistre la liste
            $this->delete($key);
            
            $files=$this->adminFiles=$this->makeList();
            
            // Met � jour les compteurs
            $nb++;
            $nbreftotal=$nbreftotal+$nbref;
        }
        
        // Ferme la base
        TaskManager::progress('Fermeture de la base... Veuillez patienter.');
        unset($this->selection);

        // 2. Tri de la base
        TaskManager::progress('Chargement termin� : '. $nbreftotal.' notices int�gr�es dans la base, d�marrage du tri');

 //       Routing::dispatch('/base/sortdb'); // TODO : workaround       
        
//        $id=TaskManager::addTask('/base/sort', 0, null, 'Tri de la base');
////        Runtime::redirect('/taskmanager/taskstatus?id='.$id);
//        echo "Lancement d'une t�che pour trier la base obtenue...<br />";
//        echo '<a href="?id='.$id.'">Voir le tri</a>';
//
//        // D�v�rouille la base
//        // TODO : D�v�rouiller la base si pas d�j� fait
          TaskManager::progress('Import termin�');
    }
    
    /**
     * V�rifie que le fichier charg� est valide :
     *  - fichier non vide
     *  - fichier tabul�
     *  - la premi�re ligne contient uniquement des noms de champs
     *  - chaque ligne contient autant de tabulations que la 1�re ligne
     */
    private function isValid($path, & $error="")
    {
        // V�rifie que le fichier n'est pas vide
        if (filesize($path) == 0)
        {
            $error='Le fichier est vide (z�ro octets)';
            return false;
        }
        // Ouvre le fichier
        $f=fopen($path,'r');

        // V�rifie que c'est un fichier tabul�
        $fields=fgetcsv($f, 0, "\t", '"');

        if (! is_array($fields) || count($fields) < 2)
        {
            $error='La premi�re ligne du fichier ne contient pas les noms de champs ou ne contient qu\'un seul nom';
            return false;
        }
        // V�rifie que la premi�re ligne contient les noms de champs
        $fields=array_flip($fields);
        if (count($t=array_diff_key($fields, $this->map)) > 0)
        {
            //$error=print_r($t,true);
            $error="champ(s) " . implode(', ', array_keys($t)) . " non g�r�(s)";
            
            return false;
        }
        return true; 
    } 
    
    // FIN IMPORT DE NOTICES

    public function actionValidateAll()
    {
        // TODO : cette fonction sert-elle encore ?
        
        // Ouvre la s�lection
        $selection=self::openDatabase('Valide=faux', false);
        if (is_null($selection)) return;

        // V�rifie qu'on a des r�ponses
        if ($selection->count==0)
        {
            echo 'Aucune notice � valider';
            return;
        }
        echo '<p>', $selection->count, ' notices � valider</p>';
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
        // TODO : cette fonction sert-elle encore ?
        
        // Ouvre la s�lection
        $selection=self::openDatabase('FinSaisie=faux', false);
        if (is_null($selection)) return;

        // V�rifie qu'on a des r�ponses
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