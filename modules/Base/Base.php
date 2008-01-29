<?php

/**
 * Module Base - Consultation de la base documentaire
 */

class Base extends DatabaseModule
{
    // TODO : A la place du template 'templates/error/error.yaml' mettre en place un système de message d'erreur.
    
    /**
     * Séparateur d'articles
     * 
     * todo: récupérer le séparateur d'articles à partir de la config, ...
     */
    const SEPARATOR=' / ';
    
    // Constantes et variables pour l'import de notices
    const dataPath='data/import/';
    const fileList='files.list';
    private $files=array();
    private $adminFiles=array();
    
    /**
     * Identifiant de la personne connectée
     * 
     * @var string
     */
    private $ident;
    
    /**
     * Table de correspondances entre le numéro d'un centre (ascoX)
     * et son numéro de l'article sur le site Ascodocpsy
     *
     * @var array
     */
    private $tblAsco=array();
    
    public function preExecute()
    {
        if (parent::preExecute()===true) return true;
     //   if ($this->action=='exportCart')
     //       $this->actionExportCart(true);
        if (Utils::isAjax() && $this->method==='actionSearch')
        {
            Config::set('template','templates/answers.html');
        }
        
        if ($this->method=='actionExportCartByType')
            $this->actionExportCartByType(true);

        // Récupère l'identifiant
        $this->ident=strtolower(User::get('login'));

        // Filtre pour les membres du GIP
        // Ils peuvent voir les notices validées et leurs propres notices si elles ne sont pas validées
        Config::set('filter.EditBase', 'ProdFich:'.$this->ident.' OR Statut:valide');

        // Charge la table de correspondances entre le numéro d'un centre (ascoX)
        // et son numéro de l'article sur le site Ascodocpsy
        $this->tblAsco=$this->loadTable('annuairegip');
    }

    /**
     * Callback utilisé pour l'affichage des notices.
     *
     * @param string $name nom de la balise mentionnée dans le template associé
     * @return string la valeur à injecter à la place de la balise $name, dans le template associé
     */
    public function getField($name)
    {
        switch ($name)
        {
            case 'Annexe':
                // Ce champ contient le ou les titres des annexes sous la forme :
                // Titre annexe 1/Titre annexe 2/Titre annexe 3
                // Il est couplé avec le champ LienAnne :
                // http://url.annexe1.fr ; http://url.annexe2.fr ; http://url.annexe3.fr
                // Si LienAnne renseigné alors, il y a toujours :
                //  - un lien et un seul par annexe
                //  - autant de titres d'annexe que de liens
                 
                if ($this->selection[$name]=='' ) return '';
                if (!$annexe=(array)$this->selection[$name]) return '';   // Cas où $this->selection[$name] est null
                
                // Si pas de lien, on affiche uniquement les titres des annexes
                if ($this->selection['LienAnne']=='') return implode(self::SEPARATOR, $annexe);
                if (!$lien=(array)$this->selection['LienAnne']) return implode(self::SEPARATOR, $annexe);
                
                $value='';
                for ($i=0;$i<=count($annexe)-1;$i++)
                {
                    if ($value) $value.=self::SEPARATOR;
                    $value.=$this->link($annexe[$i], $lien[$i], 'Accéder au texte intégral (ouverture dans une nouvelle fenêtre)', true);
                }
                return $value;

            case 'Aut':
            	if ($this->selection[$name]=='' ) return '';
                if (!$t=(array)$this->selection[$name]) return '';  // Cas où $this->selection[$name] est null

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
                return $h;
            
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
                    $h=$this->link($h, $lien, 'Notices indexées au descripteur '.$h);
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Rev':
                // Lien vers une nouvelle recherche "notices de ce périodique"
                if (! $h=trim($this->selection[$name])) return '';
                $lien='search?rev='. urlencode('['.Utils::convertString($h).']');
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
                            ($name=='Loc') ? 'Documents localisés au centre '.$h : 'Notices produites par le centre '.$h
                        );
                        $t[$key]=$lien1;
                        
                        // 2. Lien vers la présentation du centre                    
                        // Recherche le numéro de l'article correspondant à l'URL de la fiche de présentation du centre
                        // et construit le lien
                        if (isset ($this->tblAsco[$h]))
                        {                       
                            $lien2=$this->link
                            (
                                '&nbsp;<span>Présentation du centre '.$h.'</span>',
                                Config::get('urlarticle').$this->tblAsco[$h], 
                                'Présentation du centre '.$h.' (ouverture dans une nouvelle fenêtre)',
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
                
                // Notice d'un document type Périodique
                if (Utils::convertString($this->selection['Type'])=='periodique')
                {
                    if (! $lien=$this->selection['Lien']) return '';
                    
                    if (strpos(strtolower($lien), 'ascodocpsy') !== false)
                        $title='Présentation du périodique (ouverture dans une nouvelle fenêtre)';
                    else
                        $title='Accéder au texte intégral (ouverture dans une nouvelle fenêtre)';

                    // Lien vers la page de présentation de la revue ou vers le texte intégral
                    // avec ouverture dans une nouvelle fenêtre
                    return $this->link('&nbsp;<span>Présentation</span>', $lien, $title, true, 'inform');
                }
                else
                {
                    // Lien vers la page de présentation de la revue sur le site d'Ascodocpsy
                    $lien='inform?rev='. urlencode(Utils::convertString($h,'lower'));
                    return $this->link('&nbsp;<span>Présentation</span>', $lien, 'Présentation du périodique (ouverture dans une nouvelle fenêtre)', true, 'inform');
                }

            case 'EtatCol':
            	if ($this->selection[$name]=='' ) return '';
                if (! $t=(array)$this->selection[$name]) return '';
                
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

    /**
     * Callback qui retourne une chaîne vide pour chaque champ de la nouvelle
     * notice à créer, excepté pour le champ Type pour lequel il retourne son contenu
     *
     * @param string $name nom du champ de la base
     * @return string retourne une chaîne vide pour tous les champs de la base, 
     * excepté pour le champ Type (retourne sa valeur)
     */
    public function emptyString($name)
     {
        if($name==='Type')
            return $this->request->Type;
        else
            return '';
     }

    /**
     * Callback utilisé lors de la création/modification d'une fiche. 
     *
     * Il affiche, dans le formulaire de modification, le type du document
     * et les dates de création et de dernière modification.
     * 
     * @param string $name nom de la balise mentionnée dans le template associé
     * @return string la valeur à injecter à la place de la balise $name, dans le template associé
     */
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
                return $this->selection[$name];
                        
            default:
                return;                 
        }
    }

    /**
     * Filtre de validation des champs avant enregistrement dans la base.
     * 
     * @param string $name nom du champ de la base
     * @param string $value contenu du champ $name
     * @return boolean Retourne true pour les champs dont on accepte la modification,
     * false sinon
     */
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
            case 'IsbnIssn':
                // Si le champ n'est pas renseigné, alors l'action Save renvoie la valeur NULL 
                if (is_null($value)) break;
                if ( $value==='' || (is_array($value) && count($value)===0) )
                { 
                    $value=null;
                    break;
                };              
                
                // Transforme en tableau
                if (! is_array($value))
                {
                    // Le séparateur pour le champ LienAnne est ' ; ' et non pas le slash car LienAnne contient des URL
                    $sep=($name=='LienAnne') ? ';' : trim(self::SEPARATOR);
                    $value=explode($sep,$value);
                }
                
                // Supprime les chaînes vides
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
     * Transforme un fichier texte tabulé en tableau.
     * 
     * Le fichier texte est de la forme suivante :
     * 1ère ligne : Entête1[tab]Entête2
     * lignes suivantes : Valeur1[tab]Valeur2
     * 
     * Le tableau retourné a :
     *  - pour clé : les valeurs de la première colonne,
     *  - pour valeur : les valeurs de la deuxième colonne. 
     *
     * @param string $source nom du fichier en précisant éventuellement l'extension.
     * Si l'extension n'est pas mentionnée, l'extension .txt est ajoutée.
     * @return array tableau correspondant au fichier tabulé
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
        
        // Ouvre la table
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
 	 * Détermine les utilisateurs qui ont le droit de modifier une notice
 	 * 
 	 * Les administrateurs peuvent modifier toutes les notices quelque soit leur
 	 * statut (en cours, avalider, valide).
 	 * 
 	 * Les membres du GIP peuvent modifier les notices validées. Si la notice
 	 * est en cours de saisie ou à valider, seuls les membres spécifiés dans le 
 	 * champ ProdFich peuvent modifier la notice.
 	 * 
 	 * Le grand public n'a aucun droit de modification des notices.
 	 *
 	 * @return boolean retourne true si la modification est autorisée
 	 */
    public function hasEditRight()
 	{
 		// Administrateurs : peuvent modifier les notices quel que soit leur statut
 		if (User::hasAccess('AdminBase')) return true;
 		
		// Membres du GIP
 		if (User::hasAccess('EditBase'))
 		{
			// Si la notice n'a pas été validée par un administrateur, seul les
			// membres spécifiés dans le champ ProdFich peuvent modifier la notice
	        if ($this->selection['Statut'] != 'valide')
	            return (in_array($this->ident, (array)$this->selection['ProdFich'])) ? true : false;
 
			// Si la notice a été validée, tous les membres peuvent modifier la notice
			return true;
 		}

 		// Grand public : ne peuvent pas modifier les notices
 		return false;
 	}

    /**
     * Supprime, dans un auteur, les étiquettes de rôle, les mentions telles que 
     * [s.n.], collectif et les indications de nom de naissance ou d'épouse.
     * 
     * Ces suppressions sont faites afin de créer, à partir de la notice, un lien 
     * vers la bibliographie de l'auteur.
     *
     * @param string $value l'auteur tel que saisi dans la notice
     * @return string l'auteur après suppression des mentions non désirées
     */
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
        
        $value=preg_replace('~(.+)(?:(né|née|ép.)[ ].+)~','$1', $value);
        
        return trim($value);        
    }
    
    /**
     * Génère le code html pour un lien
     *
     * @param string $value libellé du lien
     * @param string $lien url du lien
     * @param string $title titre du lien
     * @param boolean $newwin ouverture ou non du lien dans une nouvelle fenêtre 
     * (optionnel, valeur par défaut false)
     * @param string $class nom de la class CSS associée au lien ((optionnel, 
     * valeur par défaut chaîne vide)
     * @return string code html du lien
     */
    private function link($value, $lien, $title, $newwin=false, $class='')
    {
        $win=($newwin) ? ' onclick="window.open(this.href); return false;"' : '';
        $c=($class) ? ' class="'.$class.'"' : '';
        return '<a'. $c. ' href="' . Routing::linkFor($lien) . '"' . $win . ' title="'.$title.'">'.$value.'</a>';        
    }

    /**
     * Lance une recherche dans la base et affiche les réponses obtenues.
     */
    public function actionSearch()
    {
        $this->cart=Module::loadModule('AscoCart');
        $this->cart->preExecute();
        parent::actionSearch();   
    }
    
    /**
     * Affiche la localisation d'une revue.
     * 
     * Recherche, dans la base, la fiche Périodique de $rev.
     * Si la fiche existe, elle est affichée en utilisant le template retourné 
     * par la fonction {@link getTemplate()} et le callback indiqué par la 
     * fonction {@link getCallback()}.
     * 
     * Dans le cas contraire, un message est affiché.
     * 
     * @param string $rev la revue à localiser
     */
    public function actionLocate($rev)
    {
        $this->request->required('rev')->unique()->ok();
        
        // Construit l'équation de recherche
        $eq='rev=['.$rev.'] et Type=periodique';
        
        // Ouvre la base de données
        $this->openDatabase();

		// Fiche Périodique inexistante
        if (! $this->select($eq))
        	return $this->showError('Aucune localisation n\'est disponible pour le périodique '.$rev.'.');
        
        // Fiche Périodique existante 
        
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
    }

    /**
     * Affiche la page de présentation d'une revue.
     * 
     * Recherche, dans la base, la fiche Périodique de $rev.
     * Si la fiche existe et que l'URL spécifiée dans le champ Lien pointe bien
     * vers le site www.ascodocpsy.org, redirige vers cette URL.
     * 
     * Un message d'erreur est affiché dans les cas suivants :
     * - fiche inexistante
     * - plusieurs fiches existent pour la revue
     * - la fiche existe mais le lien ne pointe pas vers le site www.ascodocpsy.org
     *
     * @param string $rev la revue pour laquelle on veut afficher la page 
     * de présentation
     */
    public function actionInform($rev)
    {              
        $this->request->required('rev')->unique()->ok();
        
        // Construit l'équation de recherche
        $eq='rev=['.$rev.'] et Type=periodique';
        
        // Ouvre la base de données
        $this->openDatabase();

		// Fiche Périodique inexistante
        if (! $this->select($eq))
            return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$rev.'.');
        
        // Erreur si plusieurs notices pour le périodique
        if ($this->selection->count() >= 2 )
        	return $this->showError('Il existe plusieurs notices descriptives pour le périodique '.$rev.'.');
        
        // Fiche Périodique existante mais le champ Lien ne contient pas www.ascodocpsy.org
        if (stripos($this->selection['Lien'], 'ascodocpsy') === false)
        	return $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$rev.'.');
        
        // Fiche Périodique existante, redirige vers l'URL du champ Lien (lien sur le site ascodocpsy.org)
        Runtime::redirect($this->selection['Lien'], true);
    }

    /**
     * Affiche le formulaire permettant de choisir le type du document à créer
     * Surcharge l'action New de la classe DatabaseModule
     * 
     * Si un type de document est spécifié en paramètre, affiche le formulaire 
     * de création pour ce type, sinon affiche le formulaire pour choisir le 
     * type de document à créer.
     */    
    public function actionNew()
    {
        // Si type non renseigné, affiche le formulaire pour le choisir
        if (is_null($this->request->unique('Type')->ok()))
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

//    public function actionNewsletter()
//    {
//        global $selection;
//
//        header('content-type: text/plain');
//        
//        if (is_null($equation=Utils::get($_REQUEST['equation'], null)))
//        {
//            echo "return 'Les paramètres requis n'ont pas été indiqués.';";
//            return;
//        }
//
//        // Ouvre la sélection
//        ob_start();
//        $selection=self::openDatabase($equation);
//        $h=ob_get_clean();
//        if ($h!=='' or is_null($selection))
//        { 
//            echo "return 'Impossible d\'ouvrir la base de données :<br />".addslashes($h)."';";
//            return;
//        }
//        
//        // Détermine le template à utiliser
//        if (! $template=$this->getTemplate('template'))
//        {
//            echo "return 'Le template à utiliser n'a pas été indiqué';";
//            return;
//        }
//        
//        // Détermine le callback à utiliser
//        $callback=$this->getCallback();
//
//        // Exécute le template
//        Template::run
//        (
//            $template,  
//            array($this, $callback),
//            'Base::newsletterCallback'
//        );
//        
//    }
    
    
    // ------------------- GESTION DU PANIER -------------------
    
    public function actionExportByType() // fixme: ne devrait pas être là. Mettre dans DatabaseModule un foction générique ('categorize()') et se contenter de l'appeller ici
    {
        // Ouvre la base de données (le nouveau makeEquation en a besoin)
        $this->openDatabase();

        // Détermine la recherche à exécuter        
        $this->equation=$this->getEquation();

        // Pas d'équation : erreur
        if (is_null($this->equation))
            return $this->showError('Aucun critère de recherche indiqué');

        // Lance la recherche, si aucune réponse, erreur
        if (! $this->select($this->equation, -1))
            return $this->showNoAnswer("La requête $this->equation n'a donné aucune réponse.");
    	
        // IDEA: on pourrait utiliser les "collapse key" de xapian pour faire la même chose
        // de façon beaucoup plus efficace (collapser sur type : une seule réponse par type de
        // document, get_collapse_count() donne une estimation du nombre de documents pour chaque
        // type)
        // A voir également : utiliser un MatchSpy (xapian 1.0.3)
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
            $found=$this->select($equation, -1);

            $count=$this->selection->count();
            $diff=$lastCount-$count;
            if ($diff==0)
                throw new Exception("Impossible de créer des catégories sur le champ $catField, l'équation $equation n'a pas diminué le nombre de réponses obtenues (index sans attribut count ?)");
            if ($diff<0)
                throw new Exception("Impossible de créer des catégories sur le champ $catField, l'équation $equation a augmenté le nombre de notices obtenues (utiliser l'option phraseSearch ?)");

//            echo "Réponses : $count<br />";
//            echo "différence : ", $diff, "<br />";
            
            $categories[$name]=array('equation'=>"$catIndex:$cat AND $baseEquation", 'count'=>$lastCount-$count);
            $lastCount=$count;
//            echo "lastCount passe à : $lastCount<hr />";
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
        	$url='ExportByCat?_equation='.urlencode($cat['equation']);            
            $s=($cat['count']>1)? 's' : '';
            echo "<li><a href='$url'>$name : $cat[count] notice$s</a></li>";
            $all.='&_equation='.urlencode($cat['equation']) . '&filename='.urlencode($name);
        }
        if (1 < $nbCat=count($categories))
        {
            $all=substr($all,1);
            echo "<li><a href='ExportByCat?$all'>Exporter les ", $nbCat, " fichiers </a></li>";
        }
    }
    
    /**
     * Callback utilisé pour les exports Vancouver
     *
     * @param string $name nom de la balise mentionnée dans le template associé
     * @return string la valeur à injecter à la place de la balise $name, dans le template associé
     */
    public function exportData($name)
    {
        switch ($name)
        {
            case 'Aut':
                if ($this->selection[$name]=='' ) return '';
                if (!$value=(array)$this->selection[$name]) return '';  // Cas où $this->selection[$name] est null
                
                // Récupère le format d'export
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

                // Ajoute un point à la fin du titre 
                if (strpos('.!?',substr($value,-1,1))===false) $value.='.';
                
                return $value;

            case 'PdPf':
                if (! $value=$this->selection[$name]) return '';
                
                // S'il n'y a qu'une seule page (19) ou si la pagination n'est pas continue (1 ; 3-5),
                // on laisse tel quel
                if (strpos($value,';')!==false || strpos($value,'-')===false) return $value;
                
                list($p1,$p2)=split('-',$value,2);
                $p1=trim($p1);
                $p2=trim($p2);
                
                // Si les 2 bornes de la pagination n'ont pas le même nombre de chiffres,
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
    
    // ------------------- IMPORT DE NOTICES (ancienne version) -------------------
         
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

        // Ouvre la base
        $this->openDatabase(false);

        // Récupère les champs de la base à partir de la structure
        $dbFields=$this->selection->getStructure()->fields;

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

                // Ajoute la notice
                $this->selection->addRecord();

                foreach ($data as $i=>$v)
                {
                    // Ignore les tabulations situées après le dernier champ
                    $nbRefFields++;
                    if ($nbRefFields>$nbFields) break;
                    
                    // Détermine le nom du champ dans la base
                    $fieldname=$dbFields[strtolower($fields[$i])]->name;
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
			        	case 'IsbnIssn':
			        		$v=($v=='') ? null : array_map("trim",explode(trim(self::SEPARATOR),$v));
			        		break;

			            // Champs articles : on transforme le contenu en tableau
			        	case 'LienAnne':
			        	    // Pour le champ LienAnne (Adresses Internet des annexes),
			        	    // le séparateur d'articles est le ' ; '
			        	    $v=($v=='') ? null : array_map("trim",explode(' ; ',$v));
			        	    break;

			        	default:
			        	    if ($v=='') $v=null;
			        }
                    
                    $this->selection[$fieldname]=$v;
                }
                
                // Initialisation des champs de gestion
                // Dates de création et de dernière modification
                $this->selection['Creation']=$this->selection['LastUpdate']=date('Ymd');
                
                // Statut de la notice
                // Les notices importées sont à valider par un administrateur
                $this->selection['Statut']='avalider';
                 
                // Enregistre la notice
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
        TaskManager::progress('Fermeture de la base... Veuillez patienter.');
        unset($this->selection);

        // 2. Tri de la base
        TaskManager::progress('Chargement terminé : '. $nbreftotal.' notices intégrées dans la base, démarrage du tri');

 //       Routing::dispatch('/base/sortdb'); // TODO : workaround       
        
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
     * Vérifie que le fichier chargé pour l'import est valide.
     * 
     * Les vérifications sont faites dans l'ordre suivant :
     * - le fichier n'est pas vide
     * - la première ligne du fichier n'est pas vide
     * - la première ligne fait une longueur maximum qui correspond à la longueur
     *   de l'ensemble des noms des champs de la base séparés par des tabulations
     * - la première ligne contient des tabulations
     * - la première ligne contient les noms des champs de la base
     *
     * @param string $path chemin du fichier à charger sur le serveur
     * @param string $error message de l'erreur
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

        // Lit la première ligne et supprime les espaces et les tabulations de début et fin
        $fields=trim(fgets($f, 4096)," \t\r\n");

        // Vérifie que la ligne n'est pas vide
        if ($fields=='')
        {
            $error='La première ligne du fichier est vide.';
            return false;
        }
        
        // Calcule la longueur maximale de la première ligne (doit contenir les noms des champs)
        // Ouvre la base de données et récupère les champs de la base
        $this->openDatabase();
        $dbFields=array_keys($this->selection->getStructure()->fields);
        $maxLen=strlen(implode("\t",$dbFields));
        
        // Vérifie qu'elle fait moins de $maxLen
        if (strlen($fields)>$maxLen)
        {
            $error='La première ligne du fichier fait plus de '.$maxLen.' caractères. Elle ne contient pas les noms des champs.';
            return false;
        }
        
        // Vérifie qu'elle contient des tabulations
        if (strpos($fields,"\t")===false)
        {
            $error='La première ligne du fichier ne contient pas les noms de champs ou ne contient qu\'un seul nom.';
            return false;
        }
        
        // Vérifie que la première ligne contient les noms de champs
        $fields=explode("\t",$fields);
        foreach ($fields as & $value) $value=trim($value,' "'); // Supprime les guillemets qui entourent les champs
        $dbFields=array_map('strtoupper',$dbFields);

        if (count($t=array_diff($fields, $dbFields)) > 0)
        {
            $error="champ(s) " . implode(', ', array_values($t)) . " non géré(s)";
            return false;
        }
        return true; 
    } 
    
    // FIN IMPORT DE NOTICES (ancienne version)
    
    
    // NOUVELLE VERSION DE L'IMPORT DE NOTICES (ImportModule)
    
    /**
     * Vérifie que le fichier chargé pour l'import est valide.
     * 
     * Les vérifications sont faites dans l'ordre suivant :
     * - le fichier n'est pas vide
     * - la première ligne du fichier n'est pas vide
     * - la première ligne fait une longueur maximum qui correspond à la longueur
     *   de l'ensemble des noms des champs de la base séparés par des tabulations
     * - la première ligne contient des tabulations
     * - la première ligne contient les noms des champs de la base
     *
     * @param string $path chemin du fichier à charger sur le serveur
     * @return string|bool message de l'erreur ou true si le fichier est valide
     */
    public function checkImportFile($path)
    {
        // Vérifie que le fichier n'est pas vide
        if (filesize($path) == 0)
            return 'Le fichier est vide (zéro octets)';

        // Ouvre le fichier
        $f=fopen($path,'r');

        // Lit la première ligne et supprime les espaces et les tabulations de début et fin
        $fields=trim(fgets($f, 4096)," \t\r\n");

        // Vérifie que la ligne n'est pas vide
        if ($fields=='')
            return 'La première ligne du fichier est vide.';
        
        // Calcule la longueur maximale de la première ligne (doit contenir les noms des champs)
        // Ouvre la base de données et récupère les champs de la base
        $this->openDatabase();
        $dbFields=array_keys($this->selection->getStructure()->fields);
        $maxLen=strlen(implode("\t",$dbFields));
        
        // Vérifie qu'elle fait moins de $maxLen
        if (strlen($fields)>$maxLen)
            return 'La première ligne du fichier fait plus de '.$maxLen.' caractères. Elle ne contient pas les noms des champs.';
        
        // Vérifie qu'elle contient des tabulations
        if (strpos($fields,"\t")===false)
            return 'La première ligne du fichier ne contient pas les noms de champs ou ne contient qu\'un seul nom.';
        
        // Vérifie que la première ligne contient les noms de champs
        $fields=explode("\t",$fields);
        foreach ($fields as & $value) $value=trim($value,' "'); // Supprime les guillemets qui entourent les champs
        $dbFields=array_map('strtoupper',$dbFields);

        if (count($t=array_diff($fields, $dbFields)) > 0)
            return 'Champ(s) ' . implode(', ', array_values($t)) . ' non géré(s)';

        // La première ligne du fichier contient bien les noms des champs, on lit
        // maintenant le reste du fichier
        
        // Initialise le rapport d'erreurs du fichier
        $report='';
        
        // Parcourt le fichier
        $ligne=1;
        while (($data=fgetcsv($f, 4096, "\t", '"')) !== false)
        { 
            $ligne++;
            $err='';
            
            // Vérifie que la ligne n'est pas vide
            $h=array_filter($data);
            if (count($h)==0)
            {
                $err='la ligne est vide.';
            }
            
            // Vérifie qu'il y a autant de champs que sur la première ligne
            else
            {
                if (count($data)<>count($fields))
                    $err='il n\'y a pas autant de champs que sur la première ligne du fichier (noms des champs).';
            }
            
            // Met à jour le rapport d'erreurs
            if ($err) $report.='Ligne '.$ligne.' : '.$err;
        }
        
        // Il y a des erreurs
        if ($report) return $report; 
        
        // Le fichier est valide
        return true; 
    } 
    

    public function importFile($path, $errorPath)
    {
        // Ouvre le fichier
        if (false === $f=fopen($path,'r'))
        {
            $execReport.=date('d/m/Y, H:i:s').' : Impossible d\'ouvrir le fichier '. $path.'. Le fichier n\'a pas été importé.';
            return array(false,$execReport);
        }
        
        // Heure de début de l'import
        $execReport='Début de l\'import : '.date('d/m/Y, H:i:s')."\n";
        
        // Ouvre la base de données en écriture
        $this->openDatabase(false);

        // Lit le fichier
        //TaskManager::progress($nb.'. Import du fichier ' . $file['name'], filesize($file['path']));
       
        // Lit la première ligne et récupère le nom des champs
        $fields=fgetcsv($f, 4096, "\t", '"');
        
        // Compte le nombre de champs
        $nbFields=count($fields);

        // Lit le fichier et met à jour la base
        $nbRef=0;
        while (($data=fgetcsv($f, 4096, "\t", '"')) !== false)
        {
            // Ignore les lignes vides
            $h=array_filter($data);
            if (count($h)==0) continue;

            // Initialise le nombre de champ de la notice
            $nbRefFields=0;
            
            //Taskmanager::progress(ftell($f));

            // Ajoute la notice
            $this->selection->addRecord();

            foreach ($data as $i=>$v)
            {
                // Ignore les tabulations situées après le dernier champ
                $nbRefFields++;
                if ($nbRefFields>$nbFields) break;

                // Minusculise le nom des champs 
                // On n'a pas besoin de déterminer le nom du champ à partir  
                // de la structure de la base car, pour $selection['X'], 
                // X est insensible à la casse
                $fieldname=strtolower($fields[$i]);
                
                $v=trim(str_replace('""', '"', $v));
                
                switch ($fieldname)                    
                {
                    // Champs articles : on transforme le contenu en tableau
                    case 'aut':
                    case 'motcle':
                    case 'nomp':
                    case 'candes':
                    case 'edit':
                    case 'lieu':
                    case 'etatcol':
                    case 'loc':
                    case 'prodfich':
                    case 'annexe':
                    case 'isbnissn':
                        $v=($v=='') ? null : array_map("trim",explode(trim(self::SEPARATOR),$v));
                        break;

                    // Champs articles : on transforme le contenu en tableau
                    case 'lienanne':
                        // Pour le champ LienAnne (Adresses Internet des annexes),
                        // le séparateur d'articles est le ' ; '
                        $v=($v=='') ? null : array_map("trim",explode(' ; ',$v));
                        break;

                    default:
                        if ($v=='') $v=null;
                }
                
                $this->selection[$fieldname]=$v;
            }
            
            // Initialisation des champs de gestion
            // Dates de création et de dernière modification
            $this->selection['Creation']=$this->selection['LastUpdate']=date('Ymd');
            
            // Statut de la notice
            // Les notices importées sont à valider par un administrateur
            $this->selection['Statut']='avalider';
             
            // Enregistre la notice
            $this->selection->saveRecord();
            $nbRef++;
        }
            
        //TaskManager::progress('Fichier ' . $file['name'].' : '.$nbref.' notices intégrées');
        
        // Ferme le fichier
        fclose($f);

        // Ferme la base
        //TaskManager::progress('Fermeture de la base... Veuillez patienter.');
        unset($this->selection);

        //TaskManager::progress('Import terminé');
        // Import terminé : toutes les notices ont été importées
        $execReport.='Fin de l\'import : '.date('d/m/Y, H:i:s').".\n";
        $execReport.=$nbRef. ' notices ont été importées.';
        return array(true, $execReport);
    }
    
    
}
?>