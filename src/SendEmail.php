<?php

class SendEmail
{
    /**
     * function to send mails
     * @param int $lang
     * @param string $tpl 
     * @param string $subject 
     * @param array $tplVars 
     * @param string $mailTo
     * 
     * @return bool 
     */
    private static function sendMail(int $lang, string $tpl, string $subject, array $tplVars, string $mailTo)
    {
        if (!Mail::send(
            $lang,
            $tpl,
            $subject,
            $tplVars,
            $mailTo,
            null,
            Configuration::get('_OD_SEND_EMAIL_FROM_'),
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'od_send_email' . '/mails',
            false,
            Null,
            Configuration::get('_OD_SEND_EMAIL_BCC_')
        )) {
            return false;
        }
        return true;
    }
}
