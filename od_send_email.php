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

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteFieldsValue();
    }

    public function getContent()
    {
        return $this->postProcess() . $this->displayForm();
    }

    /**
     * Post process
     * 
     * @return string
     */
    public function postProcess(): string
    {
        if (!Tools::isSubmit('submit' . $this->name)) {
            return '';
        }

        return $this->updateFieldsValue();
    }

    /**
     * Delete fields value
     * 
     * @return bool
     */
    public function deleteFieldsValue(): bool
    {
        foreach ($this->fields_values as $key => $value) {
            if (Configuration::deleteByName($key)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Update fields value
     * 
     * @return string error
     */
    public function updateFieldsValue(): string
    {
        foreach ($this->fields_values as $key => $value) {
            if ($this->validateMail($key, $value['default'])) {
                continue;
            }

            return $this->displayError($this->l('Error al actualizar ' . $value['translate']));
        }

        return $this->displayConfirmation($this->l('Datos actualizados'));
    }

    /**
     * Check if value is corrrect and update
     * 
     * @param string $value is name of input mail
     * @param string $default
     * 
     * @return bool
     */
    public function validateMail($value, $default = ''): bool
    {
        $mail = (string) Tools::getValue($value, $default);

        if (empty($mail) || !Validate::isEmail($mail)) {
            return false;
        }

        return Configuration::updateValue($value, $mail);
    }

    /**
     * send mail
     * 
     * @param int $lang 
     * @param string $name of employee customer
     * 
     * @return string
     */

    public function mailSender($lang, $name): string
    {
        if (!Mail::send(
            $lang,
            'plantilla',
            'prueba mail',
            array('{$name}' => $name),    // este array le pasa variables al tpl en este caso no lo utilizamos porq utilizamos variables globales del tpl
            Configuration::get('_OD_SEND_EMAIL_2_'),
            Null,
            Configuration::get('_OD_SEND_EMAIL_1_'),
            Null,
            Null,
            Null,
            _PS_MODULE_DIR_ . 'od_send_email/mails'
        )) {
            return $this->displayError($this->l('Error al realizar el envio'));
        }

        return $this->displayConfirmation($this->l('Correo enviado'));
    }

    public function displayForm()
    {
        $form = [[
            'form' => [
                'legend' => [
                    'title' => $this->l('Envio de correo'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_1_']['translate'],
                        'name' => '_OD_SEND_EMAIL_1_',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_2_']['translate'],
                        'name' => '_OD_SEND_EMAIL_2_',
                        'size' => 20,
                        'required' => true,
                    ],

                ],
                'buttons' => [
                    [
                        'title' => $this->l('Enviar'),
                        'class' => 'btn btn-default pull-right od_sender',
                        'icon' => 'process-icon-save',
                        'type' => 'button'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ]];

        $helper = new HelperForm();
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;
        $helper->fields_value = $this->getFieldsValue();

        return $helper->generateForm($form);
    }

    /**
     * Get fields values of helper form of configuration
     * 
     * @return array
     */

    private function getFieldsValue(): array
    {
        $data = [];

        foreach ($this->fields_values as $key => $value) {
            $data[$key] = Tools::getValue($key, Configuration::get($key));
        }

        return $data;
    }

    /**
     * Starts Front Displaying 
     */

    public function hookDisplayCustomerAccount()
    {
        return $this->displayTpl();
    }

    public function hookDisplayCustomerLoginFormAfter()
    {
        return $this->displayTpl();
    }

    public function hookActionFrontControllerSetMedia()
    {
        if ($this->context->controller->php_self != "my-account" && $this->context->controller->php_self != "authentication") {
            return;
        }

        $this->context->controller->registerStylesheet(
            'od_send_email-style',
            $this->_path . 'views/css/od_send_email.css',
            [
                'server' => 'remote',
                'media' => 'all',
                'priority' => 1000,
            ]
        );

        $this->context->controller->registerJavascript(
            'od_send_email-javascript',
            $this->_path . 'views/js/od_send_email.js',
            [
                'server' => 'remote',
                'position' => 'bottom',
                'priority' => 1000,
            ]
        );

        $this->setJSVars($this->context->customer->firstname);
    }

    /**
     * display the tpl
     * 
     * @return string
     */

    public function displayTpl()
    {
        $this->context->smarty->assign([
            'button' => $this->l('Enviar email'),
        ]);

        return $this->display(__FILE__, 'od_send_email.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addJS($this->_path . 'views/js/od_send_email.js');
        $this->setJSVars($this->context->employee->firstname);
    }

    /**
     * set js vars
     * 
     * @param string $name 
     */
    public function setJSVars($name)
    {
        if(empty($name)){
            $name="Anónimo";
        }

        Media::addJsDef([
            'name' => $name,
            'od_send_url' => Context::getContext()->link->getModuleLink('od_send_email', 'sender', array())
        ]);
    }
}
