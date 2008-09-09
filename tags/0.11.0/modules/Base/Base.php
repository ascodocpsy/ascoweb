<?php

/**
 * Module Base - Consultation de la base documentaire
 */

class Base extends DatabaseModule
{
    // TODO : A la place du template 'templates/error/error.html' mettre en place un système de message d'erreur.
    
    /**
     * Séparateur d'articles
     * 
     * todo: récupérer le séparateur d'articles à partir de la config, ...
     */
    const SEPARATOR=' / ';
    
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
        // Récupère l'identifiant
        $this->ident=strtolower(User::get('login'));

        // Filtre pour les membres du GIP
        // Ils peuvent voir les notices validées et leurs propres notices si elles ne sont pas validées
        Config::set('filter.EditBase', 'ProdFich:'.$this->ident.' OR Statut:valide');
        
        // On définit le filtre de l'accès professionnel avant d'exécuter le parent::preExecute,
        // pour que celui-ci soit pris en compte lors de la génération des fichiers d'export.
        // En effet, lors de la génération des fichiers d'export, parent::preExecute() renvoie
        // true et le code qui se trouve après n'est pas exécuté
        // (cf issue 105 : http://code.google.com/p/ascoweb/issues/detail?id=105)
        if (parent::preExecute()===true) return true;
     //   if ($this->action=='exportCart')
     //       $this->actionExportCart(true);
        if (Utils::isAjax() && $this->method==='actionSearch')
        {
            Config::set('template','templates/answers.html');
        }
        
        if ($this->method=='actionExportCartByType')
            $this->actionExportCartByType(true);

        // Charge la table de correspondances entre le numéro d'un centre (ascoX)
        // et son numéro de l'article sur le site Ascodocpsy
        $this->tblAsco=$this->loadTable('annuairegip');
    }

    /**
     * Callback utilisé pour l'affichage des notices.
     *
     * @param string $name nom de la balise mentionnée dans le template associé.
     * @return string la valeur à injecter à la place de la balise $name, dans 
     * le template associé.
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
                        $lien='Search?Aut='. urlencode('"'.$aut.'"');
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
                    $lien='Search?MotsCles='. urlencode('['.$h.']');
                    $h=$this->link($h, $lien, 'Notices indexées au descripteur '.$h);
                    $t[$key]=$h;
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Rev':
                // Lien vers une nouvelle recherche "notices de ce périodique"
                if (! $h=trim($this->selection[$name])) return '';
                $lien='Search?Rev='. urlencode('['.Utils::convertString($h).']');
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
                            'Search?'.$name.'='. urlencode($h),
                            ($name=='Loc') ? 'Documents localisés au centre '.$h : 'Notices produites par le centre '.$h
                        );
                        $t[$key]=$lien1;
                        
                        // 2. Lien vers la présentation du centre                    
                        // Recherche le numéro de l'article correspondant à l'URL de la fiche de présentation du centre
                        // et construit le lien
                        if (isset ($this->tblAsco[$h]))
                        {
                            $img=Routing::linkFor('/css/ascodocpsy/inform.png');
                            $lien2=$this->link
                            (
                                '<img src="'.$img.'" align="top" alt="Présentation" />',
                                Config::get('urlarticle').$this->tblAsco[$h], 
                                'Présentation du centre '.$h.' (ouverture dans une nouvelle fenêtre)',
                                true
                             );
                             $t[$key].='&nbsp;'.$lien2;
                        }                        
                    }
                }
                return implode(self::SEPARATOR, $t);
                
            case 'Presentation':
                if (! $h=$this->selection['Rev']) return '';
                
                // Path de l'image utilisée pour le lien
                $img=Routing::linkFor('/css/ascodocpsy/inform.png');
                
                // Notice d'un document type Périodique
                if (Utils::convertString($this->selection['Type'])=='periodique')
                {
                    if (! $lien=$this->selection['Lien']) return '';
                    
                    if (strpos(strtolower($lien), 'ascodocpsy') !== false)
                    {
                        $title='Présentation du périodique (ouverture dans une nouvelle fenêtre)';
                        $alt='Présentation';
                    }
                    else
                    {
                        $title='Accéder au texte intégral (ouverture dans une nouvelle fenêtre)';
                        $alt='Texte intégral';
                    }

                    // Lien vers la page de présentation de la revue ou vers le texte intégral
                    // avec ouverture dans une nouvelle fenêtre
                    return $this->link('<img src="'.$img.'" align="top" alt="'.$alt.'" />', $lien, $title, true);
                }
                else
                {
                    // Lien vers la page de présentation de la revue sur le site d'Ascodocpsy
                    $lien='Inform?Rev='. urlencode(Utils::convertString($h,'lower'));
                    return $this->link('<img src="'.$img.'" align="top" alt="Présentation" />', $lien, 'Présentation du périodique (ouverture dans une nouvelle fenêtre)', true);
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
                        $savCentre=$this->link($savCentre, $lien, 'Présentation du centre '.$centre.' (ouverture dans une nouvelle fenêtre)', true);
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
     * Callback utilisé lors de la création d'une notice.
     * 
     * Retourne une chaîne vide pour chaque champ de la nouvelle notice à créer, 
     * excepté :
     * - pour le champ Type : retourne le type du document 
     * - pour le champ ProdFich : retourne l'identifiant de la personne connectée 
     *
     * @param string $name nom du champ de la base.
     * @return string retourne une chaîne vide pour tous les champs de la base, 
     * excepté pour les champs Type et ProdFich.
     */
    public function emptyString($name)
     {
        switch ($name)
        {
            case 'Type':
                return $this->request->Type;
            
            case 'ProdFich':
                return $this->ident;
            
            default:
                return '';
        }
     }

    /**
     * Callback utilisé lors de la création/modification d'une fiche. 
     *
     * Il affiche, dans le formulaire de modification, les dates de création et 
     * de dernière modification.
     * 
     * @param string $name nom de la balise mentionnée dans le template associé.
     * @return string la valeur à injecter à la place de la balise $name, dans 
     * le template associé.
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
            
            default:
                return;                 
        }
    }

    /**
     * Filtre de validation des champs avant enregistrement dans la base.
     * 
     * @param string $name nom du champ de la base.
     * @param string $value contenu du champ $name.
     * @return bool retourne true pour les champs dont on accepte la modification,
     * false sinon.
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
     * Le fichier doit se trouver dans le répertoire <code>tables</code> du module.
     * 
     * Le tableau retourné a :
     *  - pour clé : les valeurs de la première colonne,
     *  - pour valeur : les valeurs de la deuxième colonne. 
     *
     * @param string $source nom du fichier en précisant éventuellement l'extension.
     * Si l'extension n'est pas mentionnée, l'extension .txt est ajoutée.
     * @return array tableau correspondant au fichier tabulé.
     */
    private function loadTable($source)
    {
        $t=array();
        
        // Ajoute l'extension par défaut s'il y a lieu
        $source=Utils::defaultExtension($source, '.txt');

        // Recherche la table dans le répertoire tables du module
        $h=Utils::searchFile($source, $this->path.'tables');        
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
 	 * Détermine les utilisateurs qui ont le droit de modifier une notice.
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
 	 * @return bool retourne true si la modification est autorisée.
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
     * @param string $value l'auteur tel que saisi dans la notice.
     * @return string l'auteur après suppression des mentions non désirées.
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
     * Génère le code html pour un lien.
     *
     * @param string $value libellé du lien.
     * @param string $lien url du lien.
     * @param string $title titre du lien.
     * @param bool $newwin ouverture ou non du lien dans une nouvelle fenêtre 
     * (optionnel, valeur par défaut false).
     * @param string $class nom de la class CSS associée au lien ((optionnel, 
     * valeur par défaut chaîne vide).
     * @return string code html du lien.
     */
    private function link($value, $lien, $title, $newwin=false, $class='')
    {
        $win=($newwin) ? ' onclick="window.open(this.href); return false;"' : '';
        $c=($class) ? ' class="'.$class.'"' : '';
//        $lien.='&' . substr(Routing::buildQueryString($this->request->getParameters()),1);
        return '<a'. $c. ' href="' . Routing::linkFor($lien) . '"' . $win . ' title="'.$title.'">'.$value.'</a>';        
    }

    /**
     * Affiche la localisation d'une revue.
     * 
     * Recherche, dans la base, la fiche Périodique de <code>$Rev</code>.
     * Si la fiche existe, elle est affichée en utilisant le template retourné 
     * par la fonction {@link getTemplate()} et le callback indiqué par la 
     * fonction {@link getCallback()}.
     * 
     * Dans le cas contraire, un message est affiché, en utilisant le template 
     * spécifié dans la clé <code><errortemplate></code> du fichier de configuration.
     * 
     * @param string $Rev la revue à localiser
     */
    public function actionLocate($Rev)
    {
        $this->request->required('Rev')->unique()->ok();
        
        // Construit l'équation de recherche
        $eq='rev=['.$Rev.'] AND Type=periodique';
        
        // Ouvre la base de données
        $this->openDatabase();

		// Fiche Périodique inexistante
        if (! $this->select($eq))
        {
        	$this->showError('Aucune localisation n\'est disponible pour le périodique '.$Rev.'.');
        	return;
        }
        
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
     * - fiche inexistante,
     * - plusieurs fiches existent pour la revue,
     * - la fiche existe mais le lien ne pointe pas vers le site www.ascodocpsy.org.
     *
     * @param string $Rev la revue pour laquelle on veut afficher la page 
     * de présentation.
     */
    public function actionInform($Rev)
    {              
        $this->request->required('Rev')->unique()->ok();
        
        // Construit l'équation de recherche
        $eq='rev=['.$Rev.'] AND Type=periodique';
        
        // Ouvre la base de données
        $this->openDatabase();

		// Fiche Périodique inexistante
        if (! $this->select($eq))
        {
            $this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$Rev.'.');
            return;
        }
        
        // Erreur si plusieurs notices pour le périodique
        if ($this->selection->count() >= 2 )
        {
        	$this->showError('Il existe plusieurs notices descriptives pour le périodique '.$Rev.'.');
        	return;
        }
        
        // Fiche Périodique existante mais le champ Lien ne contient pas www.ascodocpsy.org
        if (stripos($this->selection['Lien'], 'ascodocpsy') === false)
        {
        	$this->showError('Aucune page de présentation n\'est disponible sur le site www.ascodocpsy.org, pour le périodique '.$Rev.'.');
        	return;
        }
        
        // Fiche Périodique existante, redirige vers l'URL du champ Lien (lien sur le site ascodocpsy.org)
        Runtime::redirect($this->selection['Lien'], true);
    }

    /**
     * Affiche le formulaire permettant de choisir le type du document à créer.
     * 
     * Si un type de document est spécifié en paramètre, affiche le formulaire 
     * indiqué dans la clé <code><template></code> de la configuration, sinon
     * affiche le formulaire <code>/templates/chooseType.html</code> pour choisir le type 
     * de document à créer.
     * 
     * @param string $Type le type du document à créer.
     */    
    public function actionNew($Type='')
    {
        // Si type non renseigné, affiche le formulaire pour le choisir
        if (is_null($this->request->unique('Type')->ok()))
        {
            Template::run
            (
                'templates/chooseType.html'
            );
        }
        // Sinon, appelle l'action par défaut
        else
        {
            parent::actionNew();
        }
    }

    /**
     * Valide des notices.
     *
     * C'est en fait un chercher/remplacer ({@link actionReplace() action Replace}) : 
     * chercher "avalider", remplacer par "valide" dans le champ "Statut".
     * 
     * Affiche une demande de confirmation si l'utilisateur n'a pas confirmé la 
     * validation des notices (template <code>/templates/confirmValidate.html</code>).
     * 
     * @param string $_equation l'équation de recherche qui définit les notices
     * à valider.
     * 
     * @param bool $confirm un booléen indiquant si l'action a été confirmée.
     * Lorsque <code>$confirm</code> est à <code>false</code>, une confirmation 
     * est demandée à l'utilisateur. Lorsque <code>$confirm</code> 
     * est à <code>true</code>, l'action Replace est lancée.
     */
    public function actionValidate($_equation='', $confirm=false)
    {
        // Récupère l'équation 
        $eq=$this->request->get('_equation');

        // Demande confirmation à l'utilisateur
        if (! $confirm)
        {
            // Ajoute, à l'équation, un filtre sur les notices à valider
            $filter='Statut:avalider';
            $this->equation=$eq ? '('.$eq.') AND '.$filter : $filter;       
            
            // Vérifie qu'il y a des notices à valider
            $this->openDatabase(false);
            if (! $this->select($this->equation, -1) )
            {
                $this->showError('La recherche '. $this->equation. ' ne contient aucune notice à valider, dans la base '. Config::get('database'). '.');
                return;
            }

            // Affiche le template de confirmation
            Template::run
            (
            	'templates/confirmValidate.html'
            );
            return;
        }

        // Valide les notices
        
        // fixme : on devrait pouvoir appeler l'action Replace de la façon suivante :
        // parent::actionReplace($eq, 'avalider', 'valide', array('Statut'));
        // Pb : les paramètres de la fonction sont récupérés à partir de Request.
        // Dans le template confirmValidate, on doit donc passer les paramètres au
        // formulaire.
        parent::actionReplace($eq);
    }

    /**
     * Affiche le contenu du panier.
     * 
     * Si le panier est vide, affiche le template défini dans la clé 
     * <code><noanswertemplate></code> du fichier de configuration.
     */
    public function actionShowCaddie()
    {
        // Charge le panier
        $ascoCart=Module::getModuleFor(Request::create()->setModule('AscoCart')->setAction('Show'));
        $ascoCart->preExecute();
        
        // Le module en cours devient Base et l'action en cours devient Search
        $base=Module::getModuleFor($this->request->copy()->setModule('Base')->setAction('Search'));
        
        // Charge la configuration de l'action ShowCaddie
        Config::addArray(Config::get('actionShowCaddie'));
        
        // Panier vide
        if (count($ascoCart->cart)==0)
        {
            Template::run
            (
                $this->getTemplate('noanswertemplate')
            );
            return;
        }
         
        // Le panier contient des notices, ajoute dans la config une equation par défaut contenant toutes les notices du panier
        if (count($ascoCart->cart))
            Config::set('equation','REF:('.implode(' OR ', array_keys($ascoCart->cart)).')');
        
        // Lance /Base/Search sans paramètres pour qu'il utilise l'équation par défaut
        $base->execute();
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
    
    /**
     * Exporte des notices par type de documents.
     *
     */
    public function actionExportByType() // fixme: ne devrait pas être là. Mettre dans DatabaseModule un foction générique ('categorize()') et se contenter de l'appeller ici
    {
        // Ouvre la base de données
        $this->openDatabase();

        // Détermine la recherche à exécuter        
        $this->equation=$this->getEquation();

        // Pas d'équation : erreur
        if (is_null($this->equation))
        {
            $this->showError('Aucun critère de recherche indiqué.');
            return;
        }

        // Lance la recherche, si aucune réponse, erreur
        if (! $this->select($this->equation, -1))
        {
            $this->showNoAnswer("La requête $this->equation n'a donné aucune réponse.");
            return;
        }
    	
        // IDEA: on pourrait utiliser les "collapse key" de xapian pour faire la même chose
        // de façon beaucoup plus efficace (collapser sur type : une seule réponse par type de
        // document, get_collapse_count() donne une estimation du nombre de documents pour chaque
        // type)
        // A voir également : utiliser un MatchSpy (xapian 1.0.3)
        $lastCount=$this->selection->count();

        $equation=$baseEquation='(' . $this->equation . ')';
        
        $catField='Type';
        $catIndex='Type';
        $phraseSearch=false;
        
        $categories=array();
        for($i=0;$i<100;$i++)
        {
        	$name=$cat=$this->selection[$catField];
            if (is_array($cat)) $name=$cat=reset($cat);
            if (is_null($cat) || $cat===false || $cat==='')
            {
                $cat='__empty';
                $name='Sans type';
            }
            elseif($phraseSearch)
                $cat="[$cat]";
            elseif(strpos($cat, ' ')!==false)
                $cat="($cat)";
            
            $equation.=" -$catIndex:$cat";
            $found=$this->select($equation, -1);

            $count=$this->selection->count();
            $diff=$lastCount-$count;
            if ($diff==0)
                throw new Exception("Impossible de créer des catégories sur le champ $catField, l'équation $equation n'a pas diminué le nombre de réponses obtenues (index sans attribut count ?)");
            if ($diff<0)
                throw new Exception("Impossible de créer des catégories sur le champ $catField, l'équation $equation a augmenté le nombre de notices obtenues (utiliser l'option phraseSearch ?)");

            $categories[$name]=array('equation'=>"$catIndex:$cat AND $baseEquation", 'count'=>$lastCount-$count);
            $lastCount=$count;

            if (!$found) break;
        }

        ksort($categories);

        // Détermine le template à utiliser
        if (! $template=$this->getTemplate())
            throw new Exception('Le template à utiliser n\'a pas été indiqué');

        // Détermine le callback à utiliser
        $callback=$this->getCallback();

        // Exécute le template
        Template::run
        (
            $template,  
            array('categories'=>$categories)  
        );
        
    }
    
    /**
     * Callback utilisé pour les exports Vancouver.
     *
     * @param string $name nom de la balise mentionnée dans le template associé.
     * @return string la valeur à injecter à la place de la balise $name, dans 
     * le template associé.
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
                
                $i=6;   // On n'affiche que les 6 premiers auteurs et on met 'et al.'
                if (count($value)>$i)
                {
                    $value=array_slice($value,0,$i);
                    $value[$i]='et al.';
                    $value=implode(Config::get("formats.$format.sep"), $value);
                }
                else
                {
                    $value=implode(Config::get("formats.$format.sep"),$value);
                    // N'ajoute pas de point final, si on a une mention d'auteur (Dir.)
                    if (substr($value,-1,1)!='.') $value.='.';  
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
    
               
    // ------------------- IMPORT DE NOTICES -------------------
    
    /**
     * Vérifie que le fichier chargé pour l'import est valide.
     * 
     * Les vérifications sont faites dans l'ordre suivant :
     * - le fichier n'est pas vide,
     * - la première ligne du fichier n'est pas vide,
     * - la première ligne fait une longueur maximum qui correspond à la longueur
     *   de l'ensemble des noms des champs de la base séparés par des tabulations,
     * - la première ligne contient des tabulations,
     * - la première ligne contient les noms des champs de la base.
     *
     * @param string $path chemin du fichier à charger sur le serveur.
     * @return string|bool message de l'erreur ou true si le fichier est valide.
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
        $dbFields=array_keys($this->selection->getSchema()->fields);
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
    

    /**
     * Callback d'import d'un fichier de notices.
     *
     * @param string $path le path et le nom du fichier à importer.
     * @param string $errorPath le path du fichier dans lequel sont stockées les 
     * notices erronées.
     * @return array tableau contenant :
     * - le résultat de l'exécution : true si l'import s'est bien déroulé, false sinon
     * - le rapport d'exécution
     * - le numéro de la première notice importée
     * - le numéro de la dernière notice importée
     */
    public function importFile($path, $errorPath)
    {
        // Ouvre le fichier
        if (false === $f=fopen($path,'r'))
        {
            $execReport.=date('d/m/Y, H:i:s').' : Impossible d\'ouvrir le fichier '. $path.'. Le fichier n\'a pas été importé.';
            return array(false,$execReport,null,null);
        }
        
        // Heure de début de l'import
        $execReport='Début de l\'import : '.date('d/m/Y, H:i:s')."\n";
        
        // Ouvre la base de données en écriture
        $this->openDatabase(false);

        // Stocke la taille du fichier pour la barre de progression
        $filesize=filesize($path);

        // Lit la première ligne et récupère le nom des champs
        $fields=fgetcsv($f, 4096, "\t", '"');
        
        // Compte le nombre de champs
        $nbFields=count($fields);

        // Lit le fichier et met à jour la base
        $nbRef=$firstRef=$lastRef=0;
        while (($data=fgetcsv($f, 4096, "\t", '"')) !== false)
        {
            // Met à jour la barre de progression
            Taskmanager::progress(ftell($f), $filesize);
            
            // Ignore les lignes vides
            $h=array_filter($data);
            if (count($h)==0) continue;

            // Initialise le nombre de champ de la notice
            $nbRefFields=0;
            
            // Ajoute la notice
            $this->selection->addRecord();

            foreach ($data as $i=>$v)
            {
                // Ignore les tabulations situées après le dernier champ
                $nbRefFields++;
                if ($nbRefFields>$nbFields) break;

                // Minusculise le nom des champs 
                // On n'a pas besoin de déterminer le nom du champ à partir  
                // du schéma de la base car, pour $selection['X'], 
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
            if ($firstRef===0)
                $firstRef=$this->selection->saveRecord();
            else
                $lastRef=$this->selection->saveRecord();
                
            $nbRef++;
        }
            
        // Ferme le fichier
        fclose($f);

        // Affiche le nombre de notices importées
        if ($nbRef==1)
            echo '<p>1 notice importée (REF ', $firstRef, ')</p>';
        else
            echo '<p>', $nbRef, ' notices importées (REF ', $firstRef, ' à ', $lastRef, ')</p>';
        
        // Ferme la base
        unset($this->selection);

        // Ferme la barre de progression
        Taskmanager::progress();
        
        // Import terminé : toutes les notices ont été importées
        $execReport.='Fin de l\'import : '.date('d/m/Y, H:i:s').".\n";
        if ($nbRef==1)
            $execReport.='1 notice a été importée (REF '.$firstRef.').';
        else
            $execReport.=$nbRef. ' notices ont été importées (REF '.$firstRef.' à '.$lastRef.').';
        
        return array(true, $execReport, $firstRef, $lastRef);
    }
    
}
?>