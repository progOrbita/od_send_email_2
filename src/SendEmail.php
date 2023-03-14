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

    /**
     * function to send mail to users
     * @param int $lang
     * @param string $name
     * @param string $mail
     * 
     * @return bool
     */
    public static function toUser(int $lang, string $name, string $mail)
    {
        if (!self::sendMail($lang, 'plantilla', 'prueba usuario', ['{$name}' => $name], $mail)) {
            return false;
        }

        return true;
    }

    /**
     * function to send mail to admin when product is out of stock
     * @param int $lang
     * @param string $name
     * @param string $reference
     * @param string $imgUrl
     * @param string $urlProduct
     * 
     * @return bool
     */
    public static function outStock(int $lang, string $name, string $reference, string $imgUrl, string $urlProduct)
    {
        if (!self::sendMail($lang, 'plantillaProducto', 'out of stock', ['{$name}' => $name, '{$reference}' => $reference, '{$imgUrl}' => $imgUrl, '{$urlProduct}' => $urlProduct], Configuration::get('_OD_SEND_EMAIL_BCC_'))) {
            return false;
        }

        return true;
    }
}
