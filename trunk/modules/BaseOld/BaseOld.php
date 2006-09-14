<?php
/**
 * Module Base - Consultation de la base documentaire
 */

require_once 'Cart.php';

class BaseOld extends Module
{
    /**
     * Equation de recherche
     */
    private $equation='';
    
    /**
     * Message d'erreur lors d'une recherche
     */
    private $error='';
    
    public function preExecute()
    {
        // si c'est une action qui porte sur le panier de notice, démarre les sessions
//        if (strpos($this->action, 'Cart') !== false)
//            Config::set('sessions.use', true);
        if ($this->action=='exportCart')
            $this->actionExportCart(true);
        
        //$this->addCSS('autosize');
        
//        if ($this->action=='load')                DM : fait dans le fichier de config, maintenant
//            $this->addJavascript('autosize');
    }
    
    public function postExecute()
    {
    }
    
    public function actionSearch()
    {
        $this->doSearch('simple');
    }
    
    public function actionAdvancedSearch()
    {
        $this->doSearch('advanced');
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
        $selection=openselection($eq);

        switch ($selection->count)
        {
            case 0:
                throw new Exception('Aucune localisation n\'est disponible pour ce périodique. eq. bis:'.$selection->equation);
                break;
                
//            case 1:
//                // Définit le template
//                $tpl="${droit}_show_périodique.yaml";
//                Template::run
//                (
//                     
//                    "templates/$tpl",
//                    array
//                    (
//                        array($this     , 'baseCallback'),
//                        array('Template', 'selectionCallback')
//                    )
//                );               
//                break;
                
            default:
                $rev=Utils::convertString($rev);
                $selection->movefirst();
                while (! $selection->eof)
                {
                //echo "<li>compare [$rev] avec [" . $selection->field('Rev') . "]";
                    if ($rev == Utils::convertString($selection->field('Rev')))
                    {
                        // Définit le template
//                        if (User::hasAccess('AdminBase'))
//                            $tpl='admin';
//                        elseif (User::hasAccess('EditBase'))
                        if (User::hasAccess('EditBase,AdminBase'))
                            $tpl='member';   // TODO: SF : le template admin n'existe pas
                        else
                            $tpl='public';
                        
                        // Réouvre la sélection contenant uniquement la notice du périodique 
                        $selection=openselection('REF='. $selection->field(1), true);

                        // Affiche la notice
                        Template::run
                        (
                            "templates/${tpl}_show.yaml", 
                            array($this, 'chooseTplCallback'),
                            'Template::selectionCallback'
                        );

                        exit;
                    }
                    $selection->movenext();
                }
                throw new Exception('Aucune localisation n\'est disponible pour ce périodique. eq. bis:'.$selection->equation);
        };
    }
    
    private function doSearch($type)
    {
        global $selection;
        
        // Construit l'équation
        $this->equation=$this->makeBisEquation();
                
        if ( $this->hasErrors() )
        {
            // Détermine le template à utiliser en fonction des droits d'utilisateur
//            if (User::hasAccess('AdminBase'))
//                $tpl='admin';
//            elseif (User::hasAccess('EditBase'))
            if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
                $tpl='member';
            else
                $tpl='public';
            
            $tpl.="_${type}_search.yaml";
            
            // Montre le formulaire
            Template::run
            (
                "templates/$tpl", 
                array($this, 'chooseTplCallback'),
                $_REQUEST,
                'Template::emptyCallback'
            );
        }
        else
        {
            // Ouvre la sélection
           $selection=openselection($this->getFilter($this->equation), true);        
            
            // Charge le template
            // Affiche la notice complète si on a une seule réponse, la liste sinon
            //$tpl=($selection->count==1) ? "${droit}_show.yaml" : "${droit}_list.yaml";
            //$tpl="${droit}_list.yaml";
//            if (User::hasAccess('AdminBase'))
//                $tpl='admin';
//            elseif (User::hasAccess('EditBase'))
            if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
                $tpl='member';
            else
                $tpl='public';
            
            $tpl.='_list.yaml';
    
            Template::run
            (
                "templates/$tpl", 
                array($this, 'chooseTplCallback'),
                'Template::selectionCallback'
            );
        }
    }
    
    // ------------------- GESTION DU PANIER ------------------
    
    private function getCart()
    {
        // Crée (et charge s'il existe déjà) le panier contenant les notices
        // sélectionnées. Si le panier est modifié, il sera automatiquement
        // enregistré à la fin de la requête en cours.
        if (! isset($this->cart))
            $this->cart=new Cart('selection');
    }
    
    private function selectionFromCart()
    {
        global $selection;

        $this->getCart();
        
        // Ouvre une sélection vide
        $selection=openselection('');        

        // Ajoute dans la sélection toures les notices présentes dans le panier
        if ($this->cart->count())
            foreach ($this->cart->getItems() as $ref)
                $selection->add($ref);
    } 
    
    public function actionShowCart()
    {
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
        
        $this->selectionFromCart();        

        // Définit le template d'affichage 
        if (User::hasAccess('EditBase,AdminBase')) // TODO: SF : le template admin n'existe pas
            $tpl='member';
        else
            $tpl='public';
        $tpl.='_cart.yaml';

        Template::run
        (
            "templates/$tpl", 
            array($this, 'chooseTplCallback'),
            'Template::selectionCallback',
            array('body'=>'Les notices sélectionnées figurent dans le document joint') // TODO: à virer, uniquement parce que le génrateur ne prends pas correctement 'value' en compte
        );


    }

    /**
     * Ajoute une ou plusieurs notices dans le panier
     */
    public function actionAddToCart()
    {
        $art=Utils::get($_REQUEST['art']);
        
        if (is_null($art))
            throw new Exception("Aucun numéro de notice n'a été indiquée.");

        $this->getCart();
        $this->cart->add($art);
        $this->actionShowCart();
    }
    
    /**
     * Supprime une ou plusieurs notices du panier
     */
    public function actionRemoveFromCart()
    {
        $art=Utils::get($_REQUEST['art']);
        
        if (is_null($art))
            throw new Exception("Aucun numéro de notice n'a été indiquée.");

        $this->getCart();

        if (is_array($art))
        {
            foreach ($art as $value)
                $this->cart->remove($value);
        }
        else
        {
            $this->cart->remove($art);
        }
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
    
    public function actionExportCart($now=false)
    {
        if (! $now) return; // preExecute nous appelle avec now=true, on bosse, quand le framework nous appelle, rien à faire 
        // tout est fait dans preExecute
        $format=Utils::get($_REQUEST['format']);
        if (is_null($format))
            //$this->error='Le format n\'a pas été indiqué';
            throw new Exception("Le format n'a pas été indiqué");

        $cmd=Utils::get($_REQUEST['cmd']);
        if (is_null($cmd))
            throw new Exception("La commande n'a pas été indiquée");
            
        if ($cmd !='export' && $cmd!='mail' && $cmd !='show')
            throw new Exception("Commande incorrecte");

        if ($cmd=='mail')
        {
            $to=Utils::get($_REQUEST['to']);
            if (is_null($to))
                throw new Exception("Le destinataire n'a pas été indiqué");

            $subject=Utils::get($_REQUEST['subject']);
            if (is_null($subject))
                $subject='Notices Ascodocpsy';

            $body=Utils::get($_REQUEST['body']);
            if (is_null($body))
                $body='Le fichier ci-joint contient les notices sélectionnées';
        }
    
        // Charge la liste des formats d'export disponibles
        Config::load($this->path. 'templates/export/formats.yaml', 'formats');
        if (! $format=Config::get("formats.$format"))
            throw new Exception('Format incorrect');
        
        $template=$this->path . 'templates/export/'.$format['template'];
        if (! file_exists($template))
            throw new Exception('Format incorrect');

        // Génère l'export, en bufferisant : si on a une erreur, elle s'affiche à l'écran, pas dans le fichier
        $this->selectionFromCart();

        ob_start();        
        Template::run
        (
            $template, 
            array($this, 'chooseTplCallback'),
            'Template::selectionCallback'
        );
        $data=ob_get_clean();

        if ($cmd=='export' || $cmd=='show')
        {        
            if (isset($format['layout']))
                $this->setLayout($format['layout']);
            else
                $this->setLayout($format['none']);
    
            if (isset($format['content-type']))
                header('content-type: ' . $format['content-type']);
    
            if ($cmd=='export')
            {
                header
                (
                    'content-disposition: attachment; filename="' 
                    . (isset($format['filename']) ? $format['filename'] : 'notices.txt') 
                    . '"'
                );
            }
                        
            echo $data;
        }
        else
        {
            $this->setLayout('none');

            require_once(Runtime::$fabRoot.'htmlMimeMail5/htmlMimeMail5.php');
        
            $mail = new htmlMimeMail5();
            $mail->setFrom('Site AscodocPsy <daniel.menard@bdsp.tm.fr>');
            $mail->setSubject($subject);
            $mail->setText($body);
            $mail->addAttachment
            (
                new stringAttachment
                (
                    $data,
                    isset($format['filename']) ? $format['filename'] : 'notices.txt',
                    isset($format['content-type']) ? $format['content-type'] : 'text/plain'
                )
            );
        
            if ($mail->send( array($to)) )
            {
                echo '<p>Vos notices ont été envoyées à l\'adresse ', $to, '</p>';
            }
            else
            {
                echo "<p>Impossible d'envoyer le mail à l'adresse '$to'</p>";
            }
            
        }
        
    }
    
    // TODO : à mettre ailleurs que dans ce module
    /**
     * Crée une équation à partir des paramètres de la requête.
     * 
     * Les paramètres qui ont le même nom sont combinés en 'OU', les paramètres
     * dont le nom diffère sont combinés en 'ET'.
     * 
     * Un même paramètre peut être combiné plusieurs fois : les différentes
     * valeurs seront alors combinés en 'OU'.
     * 
     * Un paramètre peut contenir une simple valeur, une expression entre
     * guillemets doubles ou une équation de recherche combinant des valeurs ou
     * des expressions avec des opérateurs. Les opérateurs seront remplacés
     * lorsqu'ils ne figurent pas dans une expression. (avec tit="a ou b"&tit=c
     * on obtient tit="a ou b" et tit=c et non pas tit="a ou tit=b" et tit=c)
     * 
     * Lors de la création de l'équation, les paramètres dont le nom commence
     * par 'bq', les noms 'module' et 'action' ainsi que le nom utilisé
     * comme identifiant de session (sessin_name) sont ignorés. Vous pouvez
     * indiquer des noms supplémentaires à ignorer en passant dans le paramètre
     * ignore une chaine contenant les noms à ignorer (séparés par une virgule).
     * Par exemple si votre formulaire a des paramètres nommés 'max' et 'order',
     * ceux-ci seront par défaut pris en compte pour construire l'équation (vous
     * obtiendrez quelque chose du style "tit=xxx et max=100 et order=1", ce qui
     * en général n'est pas le résultat souhaité). Pour obtenir le résultat
     * correct, indiquez la chaine "max, order" en paramètre.
     * 
     * @param string $ignore optionnel, paramètres supplémentaires à ignorer.
     * (remarque : insensible à la casse).
     * 
     * @return mixed soit une chaine contenant l'équation obtenue, soit une
     * chaine vide si tous les paramètres passés étaient vides (l'utilisateur a
     * validé le formulaire de recherche sans rien remplir), soit null si aucun
     * paramètre n'a été passé dans la requête (l'utilisateur a simplement
     * demandé l'affichage du formulaire de recherche)
     */
    private function makeBisEquation($ignore='')
    {
        if ($ignore) $ignore='|' . str_replace(',', '|', preg_quote($ignore, '~'));
        $namesToIgnore="~module|action|bq.+|".preg_quote(session_name())."$ignore~i";
        
        $equation='';
        $hasFields=false;
        foreach((Runtime::isGet() ? $_GET : $_POST) as $name=>$value)
        {
            if (preg_match($namesToIgnore, $name)) continue;	
            
            $hasFields=true; // il y a au moins un nom de champ non ignoré passé en paramètre

            if (! is_array($value))
                if ($value=='') continue; else $value=array($value);
                
            $h='';
            
            foreach ($value as $item)
            {
                if ($item == '') continue;
                
                // remplace 'et/ou/sauf' par 'op $name=', mais pas dans les trucs entre guillemets
                $t=explode('"',$item);
                $parent=false;
                for ($i=0; $i<count($t); $i+=2)
                {
                    $nb=0;
                    if ($t[$i])
                    {
                        $t[$i]=str_ireplace
                        (
                            array(' ou ', ' or ', ' et ', ' sauf ', ' and not ', ' but ', ' and '),
                            array(" ou $name=", " ou $name=", " et $name=", " sauf $name=", " sauf $name=", " sauf $name=", " et $name="),
                            $t[$i],
                            $nb
                        );
                    	if ($nb) $parent=true;
                    }
                }
                $item="$name=" . implode('"',$t);
                $item=preg_replace('~'.$name.'\s*=((?:\s*\()+)~', '\1' . $name . '=', $item);
                $item=preg_replace('~\s+~', ' ', $item);
                $item=preg_replace('~=\s+~', '=', $item);
//                $item=implode('"',$t);
//                $item=preg_replace('~\(([^\(])~', '(/'.$name.'/=\1', $item);
                if ($parent) $item='(' . $item . ')';
                
                if ($h) 
                {
                    $h.=" ou $item"; 
                    $parent=true;
                }
                else
                {
                    $h=$item;
                    $parent=false;
                }
            }
                                    
            if ($parent) $h='(' . $h . ')';
            if ($h) if ($equation) $equation .= ' et ' . $h; else $equation=$h;
        }
        //echo "equation : [$equation]";
        if ($hasFields) return $equation; else return null;
    }

    /**
     * Détermine s'il y a un filtre à appliquer à l'équation de recherche,
     * en fonction des droits d'utilisateur
     * 
     * @param string $equation l'équation de recherche
     * @return string l'équation inchangée ou l'équation
     * à laquelle un filtre a été appliqué
     */
    private function getFilter($equation)
    {               
        if (! User::hasAccess('AdminBase,EditBase'))
            // TODO : A activer quand on aura plus de notices validés
            // Le grand public ne voit que les notices validées.
            // Ne voit pas les notices Périodique
            //$equation='((' . $equation . ') sauf Type=periodique) and Valide=vrai';
            $equation='(' . $equation . ') sauf Type=periodique';

        return $equation;
    }

    /**
     * Callback. Définit le template d'affichage à utiliser en fonction
     * des droits d'utilisateur, du type du document et de l'affichage
     * "liste des réponses" ou "notice complète"
     *   
     */
    public function chooseTplCallback($name)
    {
        global $selection;

//        if (User::hasAccess('AdminBase'))
//            $tpl='admin';
//        elseif (User::hasAccess('EditBase'))


        switch ($name)
        {
            case 'equation': return $this->equation . '<br />eq. bis : ' . $selection->equation . '<br />Réponses : ' . $selection->count;
            
            case 'error':
                return $this->error;
                
            case 'template':
                // Initialise le nom du template 
                if (User::hasAccess('EditBase,AdminBase')) // TODO : SF : le template admin n'existe pas
                    $tpl='member';
                else
                    $tpl='public';               

                // Même template pour les type Thèse et Mémoire
                $type=(strtolower($selection->field('Type')) == 'thèse') ? 'mémoire' : strtolower($selection->field('Type'));

                // Définit le template en fonction du nombre de réponses
                $tpl=($selection->count==1) ? "templates/${tpl}_show_$type.yaml" : "templates/${tpl}_list_$type.yaml";
                
                // Charge le template
                Template::run
                (
                    $tpl, 
                    array($this, 'baseCallback'),
                    'Template::selectionCallback'
                );
                return '';
        }
    }
 
    /**
     * 
     */
    public function baseCallback($name)
    {
        global $selection;
                
        switch ($name)
        {
            case 'Tit':
                // Lien vers texte intégral
                if (($tit=$selection->field($name)) && ($lien=$selection->field('Lien')))
                    return $this->link($tit, $lien, 'Accéder au texte intégral');
                break;
            
            case 'Annexe':
                // Lien vers texte intégral
                if (($tit=$selection->field($name)) && ($lien=$selection->field('LienAnne')))
                    return $this->link($tit, $lien, 'Accéder au texte intégral');
                break;
    
            case 'Aut':
                if (! $h=$selection->field($name)) return ;
                
                $t=explode(trim(SEPARATOR),$h);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    if ($aut=$this->author($h))
                    {
                        $lien='/base/search?aut='. urlencode($aut);
                        $h=$this->link($h, $lien, 'Bibliographie de '.$h);
                    }
                    $t[$key]=$h;
                }
                return implode(SEPARATOR, $t);
                break;
                
            case 'MotCle':
            case 'Nomp':
            case 'CanDes':
            case 'Theme':
                if (! $h=$selection->field($name)) return;
    
                $t=explode(trim(SEPARATOR), $h);
                foreach ($t as $key=>$h)
                {
                    $h=trim($h);
                    $lien='/base/search?motscles='. urlencode($h);
                    $h=$this->link($h, $lien, 'Notices indexées au descripteur '.$h);
                    $t[$key]=$h;
                }
                return implode(SEPARATOR, $t);
                break;
                
            case 'Rev':            
                if (Utils::convertString($selection->field('Type'))=='periodique' && ($lien=$selection->field('Lien')))
                {
                    // Lien vers texte intégral sur le titre du périodique
                    return $this->link($selection->field($name), $lien, 'Accéder au texte intégral');
                }
                else
                {
                    // Lien vers une nouvelle recherche "notices de ce périodique"
                    if (! $h=$selection->field($name)) return ;
                    $h=trim($h);
                    $lien='/base/search?rev='. urlencode($h);
                    return $this->link($h, $lien, 'Notices du périodique '.$h);
                };
                break;
            
            case 'DateText':
            case 'DatePub':
            case 'DateVali':
                return preg_replace('~(\d{4})\/(\d{2})\/(\d{2})~', '${3}/${2}/${1}', $selection->field($name));

            case 'Localisation': 
               if (! $h=$selection->field('Rev')) return '';
               
               // Lien vers la fiche Périodique du titre de périodique contenu dans le champ Rev,
               // pour obtenir la localisation
               $lien='locate?rev='. urlencode($h);
               return '<a class="locate" href="' . Routing::linkFor($lien) . '" title="Localiser le périodique"><span>Localiser</span></a>';

//                // Construit l'équation de recherche
//                $eq='Rev="'.$h.'" et Type=periodique';
//                
//                // Recherche la fiche Périodique
//                $sel=openselection($eq);
//                
//                switch ($sel->count)
//                {
//                    case 0:
//                        return '';
//                        
//                    case 1:
//                        $lien='/base/show?ref='. $sel->field('REF');
//                        return '<a class="locate" href="' . Routing::linkFor($lien) . '" title="Localiser le périodique"><span>Localiser</span></a>';
//                    
//                    default:
//                        $sel->movefirst();
//                        while (! $sel->eof)
//                        {
//                            if (Utils::convertString($h) == Utils::convertString($sel->field('Rev')))
//                            {
//                                $lien='/base/localiser?rev=' . urlencode($h);
////                                $lien='/base/localiser?ref='. $sel->field('REF') . '&rev=' . urlencode($h);
//                                return $this->link('Localiser', $lien, 'Localiser le périodique');
//                                break;
//                            }
//                            $sel->movenext();
//                        }
//                };
                break;

            case 'EtatCol':
                return str_replace('/', '<br />', $selection->field($name));
                break;
                
    //        default:
    //            return false;
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
    
    private function link($value, $lien, $title)
    {
        return '<a href="' . Routing::linkFor($lien) . '" title="'.$title.'">'.$value.'</a>';
    }

    /**
     * Gère les erreurs
     */
    private function hasErrors()
    {global $selection;
        // Aucun paramètre de recherche n'a été passé, il faut juste afficher le formulaire, ce n'est pas une erreur
        if (is_null($this->equation)) 
            return true;
        
        // Des paramètres ont été passés, mais tous sont vides et l'équation obtenue est vide, erreur
        if ($this->equation==='')
        {
            $this->error="Vous n'avez indiqué aucun critère de recherche.";
            return true;
        }
    
        // Aucune réponse
        $selection=openselection($this->getFilter($this->equation), true);
        if ($selection->count == 0)
        {
//          $this->error="Aucune réponse.\n<!-- Equation : $this->equation -->\n";
            $this->error="Aucune réponse. Equation : $this->equation";
            return true;
        }
        
        return false;    
    }
    
    public function actionShow()
    {
        global $selection;

//        if (User::hasAccess('AdminBase'))
//            $tpl='admin';
//        elseif (User::hasAccess('EditBase'))
        if (User::hasAccess('EditBase,AdminBase'))
            $tpl='member';  // TODO: SF : le template admin n'existe pas
        else
            $tpl='public';
       
        if ($ref=Utils::get($_REQUEST['ref']))
            $selection=openselection("ref=$ref", true);
        else
            throw new Exception('appel de show incorrect');
        
        // Charge le template
        Template::run
        (
            "templates/${tpl}_show.yaml", 
            array($this, 'chooseTplCallback'),
            'Template::selectionCallback'
        );                
    }
    
    public function actionLoad()
    {
        global $selection;
        
        if ($ref=Utils::get($_REQUEST['ref']))
        {
            // Ouvre la sélection
            $selection=openselection("ref=$ref");
        
            // Définit le type de document
            $type=(strtolower($selection->field('Type')) == 'thèse') ? 'mémoire' : strtolower($selection->field('Type'));
            
            // Charge le template
            Template::run
            (
                "templates/load/load_$type.yaml", 
                'Template::selectionCallback',
                'Template::emptyCallback'
            );
        }
        else
        {
            // TODO : template en fonction du type de document
            Template::run
            (
                'templates/load/load.yaml', 
                'Template::emptyCallback'
            );
        }
    }
    
    /**
     * Enregistre la notice
     */
    public function actionSave()
    {
        global $selection;
        
        // Ouvre la sélection
        $selection=openselection('', false); 
     
        if ($ref=Utils::get($_POST['REF']))
        {
            $selection->add($ref);
            $selection->edit();
        }
        else
        {
            $selection->addnew();
        }

        // Met à jour la notice
        for ($i=2; $i <= $selection->fieldscount; $i++)
        {
            $name=$selection->fieldname($i);
            
            switch ($name)
            {
                case 'FinSaisie':
                case 'Valide':
                    $value=Utils::get($_POST[$name], false);
                    if ($value) $value=true;
                    
//                    $value=@$_POST[$name];
//                    if (! isset($value))
//                        $value=$selection->field($name);      // SF : pourquoi cela ??
//                    else
//                    {
//                        if (! $value)
//                            $value=false;
//                        else
//                            $value=true;
//                    }
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
                
                default:
                    $value=Utils::get($_POST[$name], '');
                    if (is_array($value))
                    {
                        $value=array_filter($value);
                        $value=implode(SEPARATOR, $value);
                    }
                    break;
            }
            $selection->setfield($i, $value);
        }
        $selection->update();
        
        // Affiche la notice
        if (User::hasAccess('EditBase,AdminBase'))
            $tpl='member';  // TODO: SF : le template admin n'existe pas
        else
            $tpl='public';
       
        // Charge le template
        Template::run
        (
            "templates/${tpl}_show.yaml", 
            array($this, 'chooseTplCallback'),
            'Template::selectionCallback'
        );
    }

    /**
     * Supprime la notice
     */
    public function actionDelete()
    {        
        if (! $ref=Utils::get($_POST['REF']))
            throw new Exception("Aucun numéro de référence n'a été indiqué.");

        $selection=openselection("ref=$ref", false);
        
        switch ($selection->count)
        {
            case 0:
                throw new Exception("La fiche n°$ref n'existe pas.");
                return;
            
            case 1:        
                $selection->delete();
                echo 'La notice a été supprimée';                
                return;
                
            default:
                throw new Exception('Erreur : plusieurs fiches ont le même numéro.');
        }
    }
    
    // -------------------- Tri de la base ------------------------------
    public function actionSort()
    {
        $start_time=microtime(true);
        
        // Ouvre la sélection
        //$selection=openselection('ref=2032 or ref=2033 or ref=2094', true);
        // TODO : ouvrir la base en mode exclusif
        $selection=openselection('*', true);
        
        // Récupère la clé de tri
        $sortKey=Config::get('sortkey');
                
        // Vérrouille la base
        // TODO : Faire le lock sur la base si pas déjà fait
        
        // Crée la clé de tri
        $key=$this->createSortKey($sortKey);
        
        // Parcourt toute la sélection en créant les clés de tri
//        TaskManager::progress('Calcul des clés de tri');
//        $count=$selection->count;
//        $i=0;
        while (! $selection->eof())
        {
            $sort[$selection->field('REF')]=$this->getKey($key, $selection);
            $selection->movenext();
//            $i++;
//            TaskManager::progress($i,$count);
        }

        // Trie les clés
//        TaskManager::progress('Tri des clés');
        //asort($sort);
        arsort($sort);
        
        // Crée et ouvre la base résultat
        // Pour le moment, on part d'une base vide
        // Copie la base vide vers la base résultat
        // TODO : Ecrire un createdatabase
        $dbPath=Runtime::$root . 'data/db/' . DEFAULT_DB . '.bed';
        $dbSortPath=Runtime::$root . 'data/db/' . DEFAULT_DB . '.sort.bed';
        
        if (! copy(Runtime::$root . 'data/db/' . DEFAULT_DB . 'Vide.bed', $dbSortPath))
            throw new Exception("La copie de la base n'a pas réussi.");
        $selSort=openselection('', false, DEFAULT_DB.'.sort');
        
        // Génère la base triée
//        TaskManager::progress('Génération de la base triée');
//        $i=0;
        
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
            
//            $i++;
//            TaskManager::progress($i,$count);
        }
        echo '<p>tri réalisé en '. number_format(microtime(true) - $start_time, 2, '.', '')
             . '&nbsp;secondes</p>';
        //TaskManager::progress('Tri de la base terminé.');
       
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
    }
    
    /**
     * Crée un tableau contenant les clés utilisées pour le tri, à partir
     * de la chaîne passée en paramètre
     * 
     * @param string $sortKey chaîne contenant les clés de tri, écrites sous la
     * forme Champ1:Champ2:Champ3,Longueur de la clé (entier),Type;Autre clé
     * 
     * @return array tableau contenant les clés de tri.
     * i => fieldnames => tableau contenant les champs utilisés pour construire la clé
     *                    array(0=>Champ1, 1=>Champ2, 2=>Champ3)
     *      length     => longeur de la clé de tri
     *      type       => type à utiliser pour créer la clé
     */
    private function createSortKey($sortKey)
    {
        $key=array();
        
        // Ajoute le champ REF comme dernier champ de la clé
        $sortKey.=';Ref,6,KeyInteger';
        
        // Initialise tous les champs qui composent la clé
        $t=split(';', $sortKey);
        $i=0;
        foreach ($t as $value)
        {
            $items=split(',', trim($value));
            
            // Extrait les noms de champs
            $key[$i]['fieldnames']=split(':', trim($items[0]));
            
            // Extrait la longueur de clé
            $key[$i]['length']=$items[1];
            
            // Extrait le type
            $key[$i]['type']=trim($items[2]);
            
            $i++;       
        }
        
        // Retourne le résultat
        return $key;
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
                    $pt=strpos($value, trim(SEPARATOR));
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
                    $pt=strpos($value, trim(SEPARATOR));
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
                    $pt=strpos($value, trim(SEPARATOR));
                    if ($pt !== false)
                        $value=trim(substr($value, 0, $pt-1)); 
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, '0', STR_PAD_LEFT);
                    break;
            
                // Traiter comme un champ date Bdsp au format AAAAMMJJ
                case 'KeyDateBdsp':
                    $value=str_replace('/', '', $value);  // AAAA/MM/JJ -> AAAAMMJJ
                    if (strlen($value) > $nb)
                        $value=substr($value, 0, $nb-1);
                    else
                        $value=str_pad($value, $nb, ' ');                    
                    break;
                    
                default:
                    // TODO : que faire par défaut ?
                    
            }
            $getKey.=$value;
        }
        return $getKey;
    }
    
    
}
?>