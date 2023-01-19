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
        $this->validateField('nombre');
        $this->validateField('mail');

    /**
     * function to validate fields value 
     * 
     * @param mixed param 
     */
    
    public function validateField($param)
    {
        if (!Tools::getIsset($param) || empty(Tools::getValue($param))) {
            echo json_encode(['result' => "Parametro de ajax erroneo: " . $param]);
            die;
        }

        echo json_encode(['result' => $this->module->mailSender($this->context->language->id, Tools::getValue('nombre'))]);
        die;
    }
}
