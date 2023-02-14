<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/ControlMails.php';

class Od_send_email extends Module
{

    private $fields_values;
    private $check_date_error = '';
    private $configTabs;
    private $helperList;
    private $displayErrors = [];

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

        $this->displayName = $this->l('Send email module');
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
            && Configuration::updateValue('_OD_SEND_EMAIL_LAST_DATE_', '')
            && ControlMails::install();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteFieldsValue()
            && Configuration::deleteByName('_OD_SEND_EMAIL_CNT_')
            && Configuration::deleteByName('_OD_SEND_EMAIL_LAST_DATE_')
            && ControlMails::uninstall();
    }

    public function getContent()
    {
        $this->configTabs = [
            "tab1" => [
                "class_active" => "active",
                "class_in" => "in",
                "id" => "od_send_mail_table",
                "tittle" => "Tabla Mails",
                "content" => $this->displayHelperList()
            ],
            "tab2" => [
                "class_active" => "",
                "class_in" => "",
                "id" => "od_send_mail_config",
                "tittle" => "Configuracion del módulo",
                "content" => $this->displayForm()
            ]
        ];
        return $this->postProcess() . $this->displayAdminTpl();
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

        $this->configTabs["tab1"]['class_active'] = "";
        $this->configTabs["tab1"]['class_in'] = "";
        $this->configTabs["tab2"]['class_active'] = "active";
        $this->configTabs["tab2"]['class_in'] = "in";

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
    public function updateFieldsValue(bool $is_install = false): string
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
    public function validateMail(string $value, string $default = ''): bool
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
     * @param string $value is name of input mail
     * @param int $default
     * 
     * @return bool
     */
    public function validateInt(string $value, int $default = 0): bool
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
     * @param int $id of employee/customer
     * @param bool $is_customer customer=true employee=false
     * 
     * @return string
     */
    public function mailSender(int $lang, string $name, string $mail, int $id, bool $is_customer): string
    {
        if (!$this->checkDate()) {
            return $this->displayError($this->check_date_error);
        }

        if (!Mail::send(
            $lang,
            'plantilla',
            'prueba mail',
            ['{$name}' => $name],    // este array le pasa variables al tpl en este caso no lo utilizamos porq utilizamos variables globales del tpl
            $mail,
            Null,
            Configuration::get('_OD_SEND_EMAIL_FROM_'),
            Null,
            Null,
            Null,
            _PS_MODULE_DIR_ . $this->name . '/mails',
            false,
            Null,
            Configuration::get('_OD_SEND_EMAIL_BCC_')
        )) {
            return $this->displayError($this->l('Error al realizar el envio'));
        }

        if (!ControlMails::insert($id, $is_customer)) {
            return $this->displayError($this->l('Error al almacenar datos en la db'));
        }

        if (!$this->updateConfiguration()) {
            return $this->displayError($this->l('Error al actualizar los datos'));
        };

        return $this->displayConfirmation($this->l('Correo enviado'));
    }

    public function displayForm()
    {
        $form = [[
            'form' => [
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

        $this->setJSVars($this->context->customer->firstname ?? '', $this->context->customer->email ?? '', $this->context->customer->id ?? 0, true);
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

    /**
     * display the admin tpl
     * 
     * @return string
     */
    public function displayAdminTpl()
    {
        $err = '';
        if (!empty($this->displayErrors)) {
            foreach ($this->displayErrors as $key => $value) {
                $err .= $this->displayError($value);
            }
        }

        $this->context->smarty->assign([
            'od_send_email' => [
                'tabs' => $this->configTabs,
                'err' => $err
            ]
        ]);

        return $this->display(__FILE__, 'od_send_email_config.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addJS($this->_path . 'views/js/od_send_email.js');
        $this->setJSVars($this->context->employee->firstname, $this->context->employee->email, $this->context->employee->id, false);
    }

    /**
     * set js vars
     * 
     * @param string $name 
     * @param string $mail 
     * @param int $id 
     * @param bool $is_customer 
     */
    public function setJSVars(string $name = '', string $mail = '', int $id = 0, bool $is_customer)
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
            'id' => $id,
            'is_customer' => $is_customer,
            'od_send_url' => Context::getContext()->link->getModuleLink($this->name, 'sender',  [])
        ]);
    }

    /**
     * check if you can send email
     * 
     * @return bool 
     */
    public function checkDate()
    {
        if (Configuration::get('_OD_SEND_EMAIL_MAX_MAIL_') < 1) {
            return false;
        }

        if (empty(Configuration::get('_OD_SEND_EMAIL_LAST_DATE_'))) {
            return true;
        }

        $date = explode(' ', date('Y/M/d h:i:s'));
        $last_date = explode(' ', Configuration::get('_OD_SEND_EMAIL_LAST_DATE_'));
        if ($date[0] != $last_date[0]) {
            return $this->updateCNT(0, $this->l('dia'));
        }

        $hour = explode(':', $date[1]);
        $last_hour = explode(':', $last_date[1]);
        if ($hour[0] != $last_hour[0]) {
            return $this->updateCNT(0, $this->l('hora'));
        }

        if (Configuration::get('_OD_SEND_EMAIL_CNT_') < Configuration::get('_OD_SEND_EMAIL_MAX_MAIL_')) {
            return true;
        }

        $this->check_date_error = $this->l('Error límite de correos alcanzado');
        return false;
    }

    /**
     * update module configuration when mail was send
     * 
     * @return bool
     */
    public function updateConfiguration()
    {
        if (!$this->updateCNT(Configuration::get('_OD_SEND_EMAIL_CNT_') + 1)) {
            return false;
        }

        if (!Configuration::updateValue('_OD_SEND_EMAIL_LAST_DATE_', date('Y/M/d h:i:s'))) {
            return false;
        }

        return true;
    }

    /**
     * update cnt value
     * 
     * @param int $value value to update
     * @param string $time where error appears
     * 
     * @return bool 
     */
    public function updateCNT(int $value, string $time = '')
    {
        if (!Configuration::updateValue('_OD_SEND_EMAIL_CNT_', $value)) {
            $this->check_date_error = $this->l('Error al actualizar el contador cuando ' . $time . ' iguales.');
            return false;
        }
        return true;
    }

    /**
     * display helper list
     * 
     * @return string
     */
    private function displayHelperList(): string
    {
        $data = $this->getData();
        if (!is_array($data)) {
            return $data;
        }

        $this->helperList = new HelperList();
        $this->helperList->orderBy = Tools::getValue($this->name . '_tableOrderby', 'id');
        $this->helperList->orderWay = strtoupper(Tools::getValue($this->name . '_tableOrderway', 'ASC'));
        $this->helperList->shopLinkType = '';
        $this->helperList->simple_header = false;
        $this->setActionsHelperList();
        $this->helperList->identifier = 'id_' . $this->name . '_table';
        $this->helperList->show_toolbar = true;
        $this->helperList->table = $this->name . '_table';
        $this->helperList->token = Tools::getAdminTokenLite('AdminModules');
        $this->helperList->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        return $this->helperList->generateList($data, $this->getFieldsList());
    }

    /**
     * get db data
     * 
     * @return array|string
     */
    private function getData()
    {
        if (Tools::isSubmit('delete' . $this->name . '_table') && !empty($_GET['id_od_send_email_table'])) {
            if (ControlMails::delete($_GET['id_od_send_email_table'])) {
                $this->displayErrors[] = $this->l('Error con intentar elimnar registro db');
            }
        }

        $where = [];
        if (!Tools::isSubmit('submitReset' . $this->name . '_table')) {
            $where = $this->getFilters();
        }

        $data = ControlMails::select(Tools::getValue($this->name . '_tableOrderby', 'id_od_send_email_table'), Tools::getValue($this->name . '_tableOrderway', 'ASC'), $where);
        if ($data === false) {
            return $this->displayError($this->l('Error al obtener los datos de la base de datos'));
        }

        return $data;
    }

    /**
     * set table´s actions
     */
    private function setActionsHelperList()
    {
        $this->helperList->actions = ['delete'];
    }

    /**
     * get fields list of table
     * 
     * @return array
     */
    private function getFieldsList()
    {
        return  [
            'id_od_send_email_table' => [
                'title' => $this->l('Id'),
                'type' => 'text',
                'width' => 50,
                'orderby' => true,
            ],
            'firstname' => [
                'title' => $this->l('Nombre de usuario'),
                'align' => 'center',
                'type' => 'text',
                'orderby' => true
            ],
            'is_customer' => [
                'title' => $this->l('¿Es comprador?'),
                'align' => 'center',
                'type' => 'select',
                'list' => [0 => $this->l("No es cliente"), 1 => $this->l("Es cliente")],
                'filter_key' => 'is_customer',
                'icon' => [0 => 'disabled.gif', 1 => 'enabled.gif', 'default' => 'disabled.gif'],
                'orderby' => true
            ],
            'date_send' => [
                'title' => $this->l('Fecha de envío'),
                'type' => 'datetime',
                'orderby' => true
            ]
        ];
    }

    /**
     * get filters values
     * 
     * @return array
     */
    private function getFilters(): array
    {
        $fields = [
            'id_od_send_email_table' => 'int',
            'date_send' => 'array',
            'firstname' => 'string',
            'is_customer' => 'bool'
        ];

        $filter_table_data = [];
        foreach (Tools::getAllValues() as $key => $value) {
            if (is_array($value) && empty(array_filter($value))) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($key != 'od_send_email_tableFilter_is_customer' && ($key == 'local_od_send_email_tableFilter_date_send' || empty($value))) {
                continue;
            }

            $arr = explode('_tableFilter_', $key);
            if (count($arr) != 2) {
                continue;
            }

            if ($key == 'od_send_email_tableFilter_is_customer' && $value == '') {
                continue;
            }

            if ($fields[$arr[1]] == 'int' && !is_numeric($value)) {
                $this->displayErrors[$arr[1]] = $this->l("Error no has introducido los parametros correctos en el campo " . $arr[1]);
                continue;
            }

            if ($fields[$arr[1]] == 'array' && (!is_array($value) || (!Validate::isDate($value[0]) && !Validate::isDate($value[1])))) {
                $this->displayErrors[$arr[1]] = $this->l("Error no has introducido los parametros correctos en el campo " . $arr[1]);
                continue;
            }

            $filter_table_data[] = $this->getWhereCase($fields[$arr[1]] ?? '', $value, $arr[1]);
        }

        return $filter_table_data;
    }

    /**
     * get where sql clause
     * @param string type
     * @param mixed $value
     * @param string $key
     * 
     * @return string $where
     */
    private function getWhereCase($type, $value, $key)
    {
        switch ($type) {
            case 'int':
                $where = $key . '=' . (int) $value;
                break;

            case 'array':
                if (empty($value[0])) {
                    $value[1] .= ' 23:59:59';
                    $where = 'a.' . $key . "<= '" . $value[1] . "'";
                } elseif (empty($value[1])) {
                    $where = 'a.' . $key . ">= '" . $value[0] . "'";
                } else {
                    $value[1] .= ' 23:59:59';
                    $where = 'a.' . $key . " BETWEEN '" . $value[0] . "' AND '" . $value[1] . "'";
                }

                break;

            default:
                $where = '';
                if ($key != 'firstname') {
                    $where .= 'a.';
                } else {
                    $where .= $key . " LIKE '%" . $value . "%'";
                    return $where;
                }

                $where .= $key . "='" . $value . "'";
        }

        return $where;
    }
}
