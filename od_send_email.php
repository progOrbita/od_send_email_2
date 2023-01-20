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
            '_OD_SEND_EMAIL_FROM_' => [
                'translate' => $this->l('Remitente'),
                'default' => 'web.orbitadigital@gmail.com',
                'function' => 'validateMail'
            ],
            '_OD_SEND_EMAIL_BCC_' => [
                'translate' => $this->l('Copia'),
                'default' => 'web.orbitadigital@gmail.com',
                'function' => 'validateMail'
            ],
            '_OD_SEND_EMAIL_MAX_MAIL_' => [
                'translate' => $this->l('Maximo de mails por hora'),
                'default' => 0,
                'function' => 'validateInt'
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
            && empty($this->updateFieldsValue(true))
            && Configuration::updateValue('_OD_SEND_EMAIL_CNT_', 0)
            && Configuration::updateValue('_OD_SEND_EMAIL_LAST_DATE_', '');
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
     * @param bool is_install
     * 
     * @return string error
     */
    public function updateFieldsValue($is_install = false): string
    {
        foreach ($this->fields_values as $key => $value) {
            if ($this->{$value['function']}($key, $value['default'])) {
                continue;
            }

            return $this->displayError($this->l('Error al actualizar ' . $value['translate']));
        }

        if ($is_install) {
            return '';
        }

        return $this->displayConfirmation($this->l('Datos actualizados'));
    }

    /**
     * Check if mail value is corrrect and update
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
     * Check if number value is corrrect and update
     * 
     * @param int $value is name of input mail
     * @param string $default
     * 
     * @return bool
     */

    public function validateInt($value, $default = 0): bool
    {
        $number = (int) Tools::getValue($value, $default);
        if (!Validate::isInt($number)) {
            return false;
        }

        return Configuration::updateValue($value, $number);
    }

    /**
     * send mail
     * 
     * @param int $lang 
     * @param string $name of employee/customer
     * @param string $mail of employee/customer
     * 
     * @return string
     */

    public function mailSender($lang, $name, $mail): string
    {
        if (!$this->checkDate()) {
            return $this->displayError($this->l('Error límite de correos alcanzado'));
        }

        if (!Mail::send(
            $lang,
            'plantilla',
            'prueba mail',
            array('{$name}' => $name),    // este array le pasa variables al tpl en este caso no lo utilizamos porq utilizamos variables globales del tpl
            $mail,
            Null,
            Configuration::get('_OD_SEND_EMAIL_FROM_'),
            Null,
            Null,
            Null,
            _PS_MODULE_DIR_ . 'od_send_email/mails',
            false,
            Null,
            Configuration::get('_OD_SEND_EMAIL_BCC_')
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
                        'label' => $this->fields_values['_OD_SEND_EMAIL_FROM_']['translate'],
                        'name' => '_OD_SEND_EMAIL_FROM_',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_BCC_']['translate'],
                        'name' => '_OD_SEND_EMAIL_BCC_',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_MAX_MAIL_']['translate'],
                        'name' => '_OD_SEND_EMAIL_MAX_MAIL_',
                        'required' => true,
                        'html_content' => "<input class='form-control active' type='number' name='_OD_SEND_EMAIL_MAX_MAIL_' min=0 value='" . Configuration::get('_OD_SEND_EMAIL_MAX_MAIL_') . "'>"
                    ]
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

        $this->setJSVars($this->context->customer->firstname, $this->context->customer->email);
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
        $this->setJSVars($this->context->employee->firstname, $this->context->employee->email);
    }

    /**
     * set js vars
     * 
     * @param string $name 
     */

    public function setJSVars($name, $mail)
    {
        if (empty($name)) {
            $name = $this->l("Anónimo");
        }

        if (empty($mail)) {
            $mail = Configuration::get('_OD_SEND_EMAIL_FROM_');
        }

        Media::addJsDef([
            'name' => $name,
            'mail' => $mail,
            'od_send_url' => Context::getContext()->link->getModuleLink('od_send_email', 'sender', array())
        ]);
    }

    /**
     * check if you can send email
     * 
     * @return bool 
     */

    public function checkDate()
    {
        if (empty(Configuration::get('_OD_SEND_EMAIL_LAST_DATE_'))) {
            return true;
        }

        $date = explode(' ', date('d/M/Y h:i:s'));
        $last_date = explode(' ', Configuration::get('_OD_SEND_EMAIL_LAST_DATE_'));
        if ($date[0] != $last_date[0]) {
            return true;
        }

        $hour = explode(':', $date[1]);
        $last_hour = explode(':', $last_date[1]);
        if ($hour[0] != $last_hour[0]) {
            return true;
        }

        if (Configuration::get('_OD_SEND_EMAIL_CNT_') < Configuration::get('_OD_SEND_EMAIL_MAX_MAIL_')) {
            return true;
        }

        return false;
    }

    /**
     * update module configuration when mail was send
     */

    public function updateConfiguration()
    {
        Configuration::updateValue('_OD_SEND_EMAIL_CNT_', Configuration::get('_OD_SEND_EMAIL_CNT_') + 1);
        Configuration::updateValue('_OD_SEND_EMAIL_LAST_DATE_', date('d/M/Y h:i:s'));
    }
}
