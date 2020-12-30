<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class LAuthieTopBar extends Module implements WidgetInterface
{
	public function __construct()
	{
		$this->name = 'lauthietopbar';
        $this->author = 'Louis AUTHIE';
        $this->tab = 'front_office_features';
        $this->bootstrap ='true';
        $this->version = '1.0';
		parent::__construct();
		$this->displayName = $this->l('Louis AUTHIE Barre haut de page');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall on this module?');
		$this->description = $this->l('Ajoute une barre de promo sur le header de votre site web');
		//$this->registerHook('actionFrontControllerSetMedia');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('displayBanner') || !$this->registerHook('actionFrontControllerSetMedia') || !$this->_installSql())
			return false;
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall() && $this->_uninstallSql();
	}

	protected function _installSql()
    {
        $sqlCreate = "CREATE TABLE `" . _DB_PREFIX_ ."la_top_bar` (
			  `text` text NOT NULL,
			  `id_lang` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sqlCreateLang = "INSERT INTO `" . _DB_PREFIX_ ."la_top_bar` (`text`, `id_lang`) VALUES
				('Livraison Colissimo offerte en France avec code ENSEMBLE &amp; Option Livraison Diff&eacute;r&eacute;e pendant la p&eacute;riode de confinement', 1),
				('Free delivery Colissimo in France with code ENSEMBLE &amp; Option for delayed delivery during the confinement period', 2);";

        return Db::getInstance()->execute($sqlCreate) && Db::getInstance()->execute($sqlCreateLang);
    }

    protected function _uninstallSql()
    {
        $sql = "DROP TABLE ". _DB_PREFIX_ ."la_top_bar";
        return Db::getInstance()->execute($sql);
    }

    public function hookActionFrontControllerSetMedia($params)
	{
	    $this->context->controller->registerStylesheet(
	        'module-lauthietopbar-style',
	        'themes/bos_deerus/css/custom.css',
	        [
	          'media' => 'all',
	          'priority' => 0,
	        ]
	    );
	}

	public function hookDisplayBanner(){

		$this->getWidgetVariables(null, array());
		return $this->display(__FILE__, 'topbar.tpl');
	}

	public function getWidgetVariables($hookName, array $configuration)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'la_top_bar';
		if ($results = Db::getInstance()->ExecuteS($sql))
		$texte=array();
		foreach ($results as $content){
			$texte[$content['id_lang']]=$content['text'];
		}

		$this->smarty->assign(
			array(
				'texte'=>html_entity_decode($texte[$this->context->language->id]),
			)
		);
    }

    public function renderWidget($hookName, array $configuration)
    {
        $this->getWidgetVariables(null, array());
        return $this->fetch('module:lauthietopbar/views/templates/hook/topbar.tpl');
    }

    public function renderForm($id_image_home=null){
		// Get default language
	    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

	    // Init Fields form array
	    $fieldsForm[0]['form'] = [
	        'legend' => [
	            'title' => $this->l('Paramétrage TOP BAR'),
	        ],
	        'input' => [
	            [
	                'type' => 'text',
	                'label' => $this->l('Texte à afficher en top bar'),
	                'name' => 'contenu',
	                'lang' => true,
	                'cols' => 40,
					'rows' => 10,
	            ]
	        ],
	        'submit' => [
	            'title' => $this->l('Enregistrer les modifications'),
	            'class' => 'btn btn-default pull-right'
	        ]
	    ];

	    //Si on est en situation de modifications
	    $sql = 'SELECT * FROM '._DB_PREFIX_.'la_top_bar';
		if ($results = Db::getInstance()->ExecuteS($sql))
		foreach ($results as $content){
			$fieldValues['contenu'][$content['id_lang']]=html_entity_decode($content['text']);
		}

	    $helper = new HelperForm();
	    // Module, token and currentIndex
	    $helper->module = $this;
	    $helper->name_controller = $this->name;
	    $helper->token = Tools::getAdminTokenLite('AdminModules');
	    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

	    // Language
	    $helper->default_form_language = $defaultLang;
	    $helper->allow_employee_form_lang = $defaultLang;

	    // Title and toolbar
	    $helper->title = $this->displayName;
	    $helper->show_toolbar = true;        // false -> remove toolbar
	    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
	    $helper->submit_action = 'submit'.$this->name;
	    $helper->toolbar_btn = [
	        'save' => [
	            'desc' => $this->l('Enregistrer'),
	            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
	            '&token='.Tools::getAdminTokenLite('AdminModules'),
	        ],
	        'back' => [
	            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
	            'desc' => $this->l('Back to list')
	        ]
	    ];

	    $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $helper->fields_value = $fieldValues;

	    return $helper->generateForm($fieldsForm);
	}


	public function getContent()
	{
		$form="";
		//Traitement du formulaire
		if(Tools::isSubmit('submitlauthietopbar')){
			foreach (Language::getLanguages(false) as $lang){
				Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'la_top_bar SET text="'.htmlentities(Tools::getValue('contenu_'.$lang['id_lang'], true)).'" WHERE id_lang='.$lang['id_lang']);
			}

			$form=$this->displayConfirmation($this->l('Sauvegarde effectuée'));
		}

		//Création de la vue
		$form .= $this->renderForm();
		$form .= "<div>Pour ajouter un bouton : ".htmlentities('<a href="/url.html">Texte du bouton</a>')."</div>";

		return $form;
	}

}
