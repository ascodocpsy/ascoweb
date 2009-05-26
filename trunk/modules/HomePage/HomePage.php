<?php
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
                if (!empty($password) && ($password===Config::get('mainpassword')))
                {
                    Runtime::redirect('/HomePage/Home');
                    return;
                }
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
