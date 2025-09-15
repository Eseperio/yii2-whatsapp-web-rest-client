<?php

namespace eseperio\whatsapp\models;

use yii\base\BaseObject;
use yii\httpclient\Response;

/**
 * API Response Model
 * 
 * Represents a response from the WhatsApp Web REST API
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\models
 */
class ApiResponse extends BaseObject
{
    /**
     * @var bool Whether the request was successful
     */
    public $success;

    /**
     * @var int HTTP status code
     */
    public $statusCode;

    /**
     * @var mixed Response data
     */
    public $data;

    /**
     * @var Response Raw HTTP response object
     */
    public $rawResponse;

    /**
     * Check if the response indicates success
     * 
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->success && $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Get error message from response if available
     * 
     * @return string|null
     */
    public function getErrorMessage()
    {
        if ($this->isSuccessful()) {
            return null;
        }

        if (is_array($this->data) && isset($this->data['error'])) {
            return $this->data['error'];
        }

        if (is_array($this->data) && isset($this->data['message'])) {
            return $this->data['message'];
        }

        return 'Unknown error occurred';
    }

    /**
     * Get result data from successful response
     * 
     * @return mixed|null
     */
    public function getResult()
    {
        if (!$this->isSuccessful()) {
            return null;
        }

        if (is_array($this->data) && isset($this->data['result'])) {
            return $this->data['result'];
        }

        return $this->data;
    }

    /**
     * Get specific field from response data
     * 
     * @param string $field Field name
     * @param mixed $default Default value if field not found
     * @return mixed
     */
    public function get($field, $default = null)
    {
        if (is_array($this->data) && isset($this->data[$field])) {
            return $this->data[$field];
        }

        return $default;
    }

    /**
     * Convert response to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'success' => $this->success,
            'statusCode' => $this->statusCode,
            'data' => $this->data,
        ];
    }
}