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
        $id = (int) Tools::getValue('id', 0);
        if ($id < 0) {
            die(json_encode(['result' => $this->module->displayError($this->module->l("Id incorrecto."))]));
        }

        die(json_encode(['result' => $this->module->mailSender((int) $this->context->language->id, $name, $email, $id, $is_customer)]));
    }

    /**
     * function to validate fields value 
     * 
     * @param string param 
     * @return string|void 
     */
    public function validateField(string $param): string
    {
        $value = trim((string) Tools::getValue($param, ''));

        if (empty($value) || ($param == 'mail' && !Validate::isEmail($value))) {
            die(json_encode(['result' => $this->module->displayError($this->module->l("Parametro de ajax erroneo: ") . $param)]));
        }

        return $value;
    }
}
