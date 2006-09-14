<?php
/*
 * Created on 31 mai 06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class HomePage extends Module
{
    public function preExecute()
    {
    	switch ($this->action)
        {
            case 'home' :
                $this->setLayout('homepage.htm');
                break;
            case 'index':
                $password=Utils::get($_POST['password']);
                if ($password=='poivron')
                    Runtime::redirect('/HomePage/home');
                $this->setLayout('default.htm');
                break;
            default: die();
        }
            
    }
    
	public function actionHome()
    {
    	// jamais appellé parce que le layout 'demo' ne contient pas de balise 'contents'
    }
    
    public function actionIndex()
    {
        Template::run('login.yaml');
    }
}
?>
