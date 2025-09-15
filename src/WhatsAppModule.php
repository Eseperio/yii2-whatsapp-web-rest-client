<?php

namespace eseperio\whatsapp;

use yii\base\Module;

/**
 * WhatsApp Web REST Client Module
 * 
 * This module provides integration with WhatsApp Web REST API
 * through the avoylenko/wwebjs-api docker container.
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp
 */
class WhatsAppModule extends Module
{
    /**
     * @var string the module ID
     */
    public $id = 'whatsapp';

    /**
     * @var bool Enable/disable session management features
     */
    public $enableSessionManagement = true;

    /**
     * @var bool Enable/disable message features
     */
    public $enableMessaging = true;

    /**
     * @var bool Enable/disable contact management features
     */
    public $enableContactManagement = true;

    /**
     * @var bool Enable/disable group chat features
     */
    public $enableGroupChat = true;

    /**
     * @var bool Enable/disable channel features
     */
    public $enableChannels = true;

    /**
     * @var bool Enable/disable media handling features
     */
    public $enableMedia = true;

    /**
     * @var bool Enable/disable caching for API requests
     */
    public $enableCache = false;

    /**
     * @var string Name of the cache component to use
     */
    public $cacheComponent = 'cache';

    /**
     * @var int Cache duration in seconds (default: 5 minutes)
     */
    public $cacheDuration = 300;

    /**
     * @var array Component configuration for whatsappClient
     */
    public $whatsappClientConfig = [
        'class' => 'eseperio\whatsapp\components\WhatsAppClient',
        'baseUrl' => 'http://localhost:3000',
        'apiKey' => null,
        'defaultSessionId' => 'default',
        'timeout' => 30,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Pass cache configuration to the WhatsApp client
        if ($this->enableCache) {
            $this->whatsappClientConfig['enableCache'] = $this->enableCache;
            $this->whatsappClientConfig['cacheComponent'] = $this->cacheComponent;
            $this->whatsappClientConfig['cacheDuration'] = $this->cacheDuration;
        }

        // Register the WhatsApp client component
        $this->setComponents([
            'whatsappClient' => $this->whatsappClientConfig,
        ]);
    }

    /**
     * Get the WhatsApp client component
     * 
     * @return \eseperio\whatsapp\components\WhatsAppClient
     */
    public function getWhatsappClient()
    {
        return $this->get('whatsappClient');
    }
}