<?php
/**
 * Panier
 */

class Cart
{
    /**
     * @var string Nombre maximum d'items autorisés dans le panier
     * @access public
     */
    public $maxCount=10000;
    
    /**
     * @var array Tableau contenant les items du panier
     * @access private
     */
    private $items;
    
    /**
     * @var boolean Indique si le panier contient des items ou uniquement des
     * clés.
     */
    private $hasValues=false;
    
    /**
     * Constructeur. Crée (et charge s'il existe déjà) le panier indiqué.
     * Le panier sera automatiquement enregistré à la fin de la requête en
     * cours.
     * 
     * @param string $name le nom du panier
     */
    public function __construct($name)
    {
        // Crée le panier s'il n'existe pas déjà dans la session
        if (!isset($_SESSION[$name])) 
            $_SESSION[$name]=array();
        
        // Crée une référence entre notre tableau item et le tableau stocké 
        // en session pour que le tableau de la session soit automatiquement modifié
        // et enregistré 
        $this->items =& $_SESSION[$name];
    }

    /**
     * Ajoute un item dans le panier. Si un item ayant la même clé figurait déjà
     * dans le panier, il est écrasé.
     * 
     * @param mixed $key la clé de l'item à ajouter
     * @param mixed $item optionnel : les données à associer à la clé. Si votre
     * panier contient de l'information structurée, vous passerez en général un
     * tableau associatif. Si votre panier contient des éléments simples (par
     * exemple une liste de notices pertinentes), vous passerez uniquement
     * le champ REF comme paramètre $key.
     */
    public function add($key, $item=null)
    {
        if ($this->count() > $this->maxCount)
            throw new Exception("Le panier est plein, impossible d'ajouter qq chose");
            
        if (is_int($key) || ctype_digit($key)) $key=(int)$key;
        if ($item)
        {
            $this->items[$key]=$item;
            $this->hasValues=true;
        }
        else
        {
            if (is_array($key))
            {
                foreach ($key as $value)
                {
                    if (is_int($value) || ctype_digit($value)) $value=(int)$value;
                    $this->items[$value]=$value;
                }
            }
            else
            {
                $this->items[$key]=$key;
            }
        }
        //$this->dump();
    }
    
    /**
     * Supprime du panier l'élément dont la clé est passée en paramètre.
     * remarque : Aucune erreur n'est générée si l'item ne figurait pas dans le
     * panier
     * @param mixed $key
     */
    public function remove($key)
    {
        if (is_int($key) || ctype_digit($key)) $key=(int)$key;
        unset($this->items[$key]);
    }
    
    /**
     * Vide le panier
     */
    public function clear()
    {
        $this->items=null;
    }
    
    /** 
     * Retourne le nombre d'éléments d'items dans le panier
     */
    public function count()
    {
        return count($this->items);
    }
    
    /**
     * Retourne un tableau contenant tous les items présents dans le panier.
     * @return array
     */
    public function & getItems()
    {
        return $this->items;
    }
    
//    /**
//     * Si le tableau ne contient que des clés, optimize un peu le tableau pour
//     * ne sérialiser que les clés.
//     */
//    public function __sleep()
//    {
//    	if (! $this->hasValues)
//            $this->items=implode('/',array_keys($this->items));
//        return array('items');
//    }

//    /**
//     * Si seules les clés du tableau ont été enregistrées, restaure le tableau
//     * sous la forme attendue par les autres méthodes (clé=valeur)
//     */
//    public function __wakeup()
//    {
//        if (! $this->hasValues)
//        {
//        	$t=explode('/',$this->items);
//            $this->items=array_combine($t,$t);
//        }
//    }
    
//    public function dump()
//    {
//        
//        echo'<h2>Etat du panier</h2><pre>';
//        var_dump($this);
//        echo'</pre>';
//    }
}

?>