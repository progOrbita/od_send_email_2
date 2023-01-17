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
        return $this->postProcess($this->context->employee->id_lang, $this->context->employee->firstname) . $this->displayForm();
    }

    /**
     * Post process
     * 
     * @param int $lang
     * @param string $name of employee or customer
     * 
     * @return string
     */
    public function postProcess($lang, $name): string
    {
        if (!Tools::isSubmit('submit' . $this->name)) {
            return '';
        }

        $result = $this->updateFieldsValue();
        if (!empty($result)) {
            return $result;
        }

        return $this->mailSender($lang, $name);
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

        return '';
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
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Enviar'),
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
}
