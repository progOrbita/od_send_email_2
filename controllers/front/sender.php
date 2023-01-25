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

        if (!Tools::getIsset('id') && Tools::getValue('id', 0) < 0) {
            die(json_encode(['result' => $this->module->l("Id incorrecto.")]));
        }

        die(json_encode(['result' => $this->module->mailSender($this->context->language->id, $name, $email, Tools::getValue('id'), $is_customer)]));
    }

    /**
     * function to validate fields value 
     * 
     * @param mixed param 
     */
    public function validateField($param)
    {
        $value = trim(Tools::getValue($param, ''));

        if (empty(Tools::getValue($param))) {
            die(json_encode(['result' => "Parametro de ajax erroneo: " . $param]));
        }

        return $value;
    }
}
