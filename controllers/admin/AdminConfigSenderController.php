<?php

class AdminConfigSenderController extends ModuleAdminController
{
    private $fields_values = [];
    private $configTabs = [];
    private $displayErrors = [];
    private $helperList;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->fields_values = $this->module->getFields_values();
    }

    public function initContent()
    {
        $this->displayErrors = [];
        parent::initContent();
        $err = '';
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

        $content = $this->postProcessForm();
        if (!empty($this->displayErrors)) {
            foreach ($this->displayErrors as $key => $value) {
                $err .= $this->module->displayError($value);
            }
        }

        $this->context->smarty->assign([
            'od_send_email' => [
                'tabs' => $this->configTabs,
                'err' => $err
            ]
        ]);

        $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/od_send_email/views/templates/hook/od_send_email_config.tpl');

        $this->context->smarty->assign([
            'content' => $content
        ]);
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
                        'title' => $this->module->l('Enviar'),
                        'class' => 'btn btn-default pull-right od_sender',
                        'icon' => 'process-icon-save',
                        'type' => 'button'
                    ]
                ],
                'submit' => [
                    'title' => $this->module->l('Guardar'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ]];

        $helper = new HelperForm();
        $helper->token = $this->token;
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->module->name]);
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->fields_value = $this->getFieldsValues();

        return $helper->generateForm($form);
    }

    /**
     * Get fields values of helper form of configuration
     * 
     * @return array
     */
    private function getFieldsValues(): array
    {
        $data = [];

        foreach ($this->fields_values as $key => $value) {
            $data[$key] = Tools::getValue($key, Configuration::get($key));
        }

        return $data;
    }

    /**
     * Update fields value
     * 
     * @return string error|confirmation
     */
    public function updateFieldsValue(): string
    {
        foreach ($this->fields_values as $key => $value) {
            if ($this->{$value['function']}($key, $value['default'])) {
                continue;
            }

            return $this->module->displayError($this->module->l('Error al actualizar ' . $value['translate']));
        }

        $this->configTabs["tab2"]['content'] = $this->displayForm();
        return $this->module->displayConfirmation($this->module->l('Datos actualizados'));
    }

    /**
     * Post process
     * 
     * @return string
     */
    public function postProcessForm(): string
    {
        if (!Tools::isSubmit('submit' . $this->module->name)) {
            return '';
        }

        $this->configTabs["tab1"]['class_active'] = "";
        $this->configTabs["tab1"]['class_in'] = "";
        $this->configTabs["tab2"]['class_active'] = "active";
        $this->configTabs["tab2"]['class_in'] = "in";

        return $this->updateFieldsValue();
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
        if (!Validate::isInt($number) || $number < 0) {
            return false;
        }

        return Configuration::updateValue($value, $number);
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
        $this->helperList->orderBy = Tools::getValue($this->module->name . '_tableOrderby', 'id');
        $this->helperList->orderWay = strtoupper(Tools::getValue($this->module->name . '_tableOrderway', 'ASC'));
        $this->helperList->shopLinkType = '';
        $this->helperList->simple_header = false;
        $this->setActionsHelperList();
        $this->helperList->identifier = 'id_' . $this->module->name . '_table';
        $this->helperList->show_toolbar = true;
        $this->helperList->table = $this->module->name . '_table';
        $this->helperList->token = $this->token; //to fix invalid token
        $this->helperList->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;
        return $this->helperList->generateList($data, $this->getFieldsList());
    }

    /**
     * get db data
     * 
     * @return array|string
     */
    private function getData()
    {
        if (Tools::isSubmit('delete' . $this->module->name . '_table') && !empty($_GET['id_od_send_email_table'])) {
            if (ControlMails::delete($_GET['id_od_send_email_table'])) {
                $this->displayErrors[] = $this->module->l('Error con intentar elimnar registro db');
            }
        }

        $where = [];
        if (!Tools::isSubmit('submitReset' . $this->module->name . '_table')) {
            $where = $this->getFilters();
        }

        $data = ControlMails::select(Tools::getValue($this->module->name . '_tableOrderby', 'id_od_send_email_table'), Tools::getValue($this->module->name . '_tableOrderway', 'ASC'), $where);
        if ($data === false) {
            return $this->module->displayError($this->l('Error al obtener los datos de la base de datos'));
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
                'title' => $this->module->l('Id'),
                'type' => 'text',
                'width' => 50,
                'orderby' => true,
            ],
            'firstname' => [
                'title' => $this->module->l('Nombre de usuario'),
                'align' => 'center',
                'type' => 'text',
                'orderby' => true
            ],
            'is_customer' => [
                'title' => $this->module->l('¿Es comprador?'),
                'align' => 'center',
                'type' => 'select',
                'list' => [0 => $this->module->l("No es cliente"), 1 => $this->module->l("Es cliente")],
                'filter_key' => 'is_customer',
                'icon' => [0 => 'disabled.gif', 1 => 'enabled.gif', 'default' => 'disabled.gif'],
                'orderby' => true
            ],
            'date_send' => [
                'title' => $this->module->l('Fecha de envío'),
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
