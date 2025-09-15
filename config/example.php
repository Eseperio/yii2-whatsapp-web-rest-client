<?php
/**
 * Example configuration for the WhatsApp Web REST Client module
 * 
 * Add this to your main application config file (web.php or console.php)
 */

return [
    'modules' => [
        'whatsapp' => [
            'class' => 'eseperio\whatsapp\WhatsAppModule',
            
            // Feature control - enable/disable specific functionalities
            'enableSessionManagement' => true,
            'enableMessaging' => true,
            'enableContactManagement' => true,
            'enableGroupChat' => true,
            'enableChannels' => true,
            'enableMedia' => true,
            
            // WhatsApp client component configuration
            'whatsappClientConfig' => [
                'class' => 'eseperio\whatsapp\components\WhatsAppClient',
                
                // Base URL of your wwebjs-api container
                'baseUrl' => 'http://localhost:3000',
                
                // API key for authentication (optional, set in wwebjs-api container)
                'apiKey' => null, // or 'your-api-key'
                
                // Default session ID to use when none specified
                'defaultSessionId' => 'default',
                
                // Request timeout in seconds
                'timeout' => 30,
            ],
        ],
    ],
    
    // Alternative: Configure as a direct component
    /*
    'components' => [
        'whatsapp' => [
            'class' => 'eseperio\whatsapp\components\WhatsAppClient',
            'baseUrl' => 'http://localhost:3000',
            'apiKey' => null,
            'defaultSessionId' => 'main-session',
            'timeout' => 60,
        ],
    ],
    */
];