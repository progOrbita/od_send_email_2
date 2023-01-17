<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Od_send_email extends Module
{

    private $fields_values;

    public function __construct()
    {
        $this->name = 'od_send_email';
        $this->tab = 'front_office_features'; // TODO invest
        $this->version = '1.0.0';
        $this->author = 'Jose Barreiro';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.1',
            'max' => '1.7.9',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('od_send_email');
        $this->description = $this->l('Module to send mails.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->fields_values = [
            '_OD_SEND_EMAIL_1_' => [
                'translate' => $this->l('Remitente'),
                'default' => 'web.orbitadigital@gmail.com'
            ],
            '_OD_SEND_EMAIL_2_' => [
                'translate' => $this->l('Receptor'),
                'default' => 'web.orbitadigital@gmail.com'
            ]
        ];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayCustomerLoginFormAfter')
            && empty($this->updateFieldsValue());
    }
}
