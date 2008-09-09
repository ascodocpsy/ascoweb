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
    	switch ($this->method)
        {
            case 'actionHome' :
                $this->setLayout('homepage.html');
                break;
            case 'actionIndex':
                $password=Utils::get($_POST['password']);
                if ($password=='poivron')
                    Runtime::redirect('/HomePage/Home');
                $this->setLayout('default.htm');
                break;
            default: die(__METHOD__.' : action non reconnue <pre>'.print_r($this,true));
        }
            
    }
    
	public function actionHome()
    {
    	// jamais appellé parce que le layout 'demo' ne contient pas de balise 'contents'
    }
    
    public function actionIndex()
    {
        Template::run('login.html');
    }
}
?>
