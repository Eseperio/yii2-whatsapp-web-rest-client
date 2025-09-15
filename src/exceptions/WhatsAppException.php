<?php

namespace eseperio\whatsapp\exceptions;

use yii\base\Exception;

/**
 * WhatsApp Exception
 * 
 * Exception thrown by WhatsApp Web REST client operations
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\exceptions
 */
class WhatsAppException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'WhatsApp API Exception';
    }
}