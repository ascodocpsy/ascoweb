<?php
/*
 * Created on 31 mai 06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

class SpipSecurity extends NoSecurity
{
    public function __construct()
    {
    	//print_r($_COOKIE);
        $this->rights=empty($_COOKIE['user']) ? '' : $_COOKIE['user'];
    }	

    public function preExecute()
    {
//        Config::set('sessions.use', true);  

        switch(@$_REQUEST['statut'])
        {
            case '6forum': // visiteur
                $rights='';
                break;
            case '1comite': // rédacteur
                $rights='Edit';
                break;
            case '0minirezo': // administrateur
                $rights='Admin';
                break;
            default:
                throw new Exception('Connexion impossible');
        }
        
        $url=empty($_REQUEST['url']) ? '/base/searchform' : $_REQUEST['url'];
        
        setcookie('user', $rights, 0, '/');
        Runtime::redirect($url);
    }
    
    public function index()
    {
        // tout est fait dans preExecute
        
        // TODO: permettre de dire 'stop' au framework quand on est dans preExecute
    }
}

?>
