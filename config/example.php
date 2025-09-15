<?php
/**
 * Example configuration for the WhatsApp Web REST Client
 * 
 * Add this to your main application config file (web.php or console.php)
 */

return [
    // Recommended: Configure as main application component
    'components' => [
        'whatsapp' => [
            'class' => 'eseperio\whatsapp\components\WhatsAppClient',
            
            // Base URL of your wwebjs-api container
            'baseUrl' => 'http://localhost:3000',
            
            // API key for authentication (optional, set in wwebjs-api container)
            'apiKey' => null, // or 'your-api-key'
            
            // Default session ID to use when none specified
            'defaultSessionId' => 'default',
            
            // Request timeout in seconds
            'timeout' => 30,
            
            // Caching configuration (optional)
            'enableCache' => false,       // Enable caching for better performance
            'cacheComponent' => 'cache',  // Cache component name
            'cacheDuration' => 300,       // Cache duration in seconds (5 minutes)
        ],
    ],
    
    // Alternative: Module configuration (for backward compatibility or advanced features)
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
];