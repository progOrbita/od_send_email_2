<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/ControlMails.php';
require_once __DIR__ . '/src/SendEmail.php';

class Od_send_email extends Module
{

    private $fields_values;
    private $check_date_error = '';

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
            && $this->registerHook('actionUpdateQuantity')
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
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminConfigSender'));
    }

    public function getFields_values()
    {
        return $this->fields_values;
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

        if (!SendEmail::toUser($lang, $name, $mail)) {
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
            $this->check_date_error = $this->l('Error no ha configurado un minimo numero de mails');
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
}
