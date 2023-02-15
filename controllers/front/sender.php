<?php

/**
 * <ModuleClassName> => Od_Send_Email
 * <FileName> => sender.php
 * Format expected: <ModuleClassName><FileName>ModuleFrontController
 */
class Od_Send_EmailSenderModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $name = $this->validateField('nombre');
        $email = $this->validateField('mail');
        $is_customer = Tools::getValue('is_customer') == "true";

        if (!Tools::getIsset('id') || Tools::getValue('id', 0) < 0) {
            die(json_encode(['result' => $this->module->displayError($this->module->l("Id incorrecto."))]));
        }

        die(json_encode(['result' => $this->module->mailSender((int) $this->context->language->id, (string) $name, (string) $email, (int) Tools::getValue('id'), (bool) $is_customer)]));
    }

    /**
     * function to validate fields value 
     * 
     * @param mixed param 
     */
    public function validateField($param)
    {
        $value = trim(Tools::getValue($param, ''));

        if (empty($value)) {
            die(json_encode(['result' => $this->module->displayError($this->module->l("Parametro de ajax erroneo: ") . $param)]));
        }

        if ($param == 'mail' && !Validate::isEmail($value)) {
            die(json_encode(['result' => $this->module->displayError($this->module->l("Parametro de ajax erroneo: ") . $param)]));
        }


        return $value;
    }
}
