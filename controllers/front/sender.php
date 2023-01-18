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
        if (!Tools::getIsset('nombre') || empty(Tools::getValue('nombre'))) {
            echo json_encode(['result' => false]);
            die;
        }

        echo json_encode(['result' => $this->module->mailSender($this->context->language->id, Tools::getValue('nombre'))]);
        die;
    }
}
