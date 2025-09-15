<?php
/**
 * Integration patterns and real-world usage examples
 * 
 * This file demonstrates practical integration patterns for the WhatsApp client
 */

use eseperio\whatsapp\exceptions\WhatsAppException;
use yii\helpers\Console;

// Example business logic classes that might use the WhatsApp client

/**
 * Customer notification service example
 */
class CustomerNotificationService
{
    private $whatsapp;
    private $sessionId;

    public function __construct($whatsapp, $sessionId = 'customer-service')
    {
        $this->whatsapp = $whatsapp;
        $this->sessionId = $sessionId;
    }

    public function sendOrderConfirmation($customerPhone, $orderDetails)
    {
        $chatId = $this->whatsapp->formatToWhatsAppId($customerPhone);
        
        $message = "🛍️ *Order Confirmation*\n\n";
        $message .= "Thank you for your order!\n\n";
        $message .= "*Order ID:* {$orderDetails['id']}\n";
        $message .= "*Total:* \${$orderDetails['total']}\n";
        $message .= "*Status:* {$orderDetails['status']}\n\n";
        $message .= "We'll notify you when your order ships.";

        return $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
    }

    public function sendShippingNotification($customerPhone, $trackingNumber)
    {
        $chatId = $this->whatsapp->formatToWhatsAppId($customerPhone);
        
        $message = "📦 *Your order has shipped!*\n\n";
        $message .= "Tracking number: `{$trackingNumber}`\n\n";
        $message .= "You can track your package at: [tracking-url]";

        return $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
    }

    public function sendAppointmentReminder($customerPhone, $appointmentDetails)
    {
        $chatId = $this->whatsapp->formatToWhatsAppId($customerPhone);
        
        $message = "⏰ *Appointment Reminder*\n\n";
        $message .= "You have an appointment scheduled:\n\n";
        $message .= "*Date:* {$appointmentDetails['date']}\n";
        $message .= "*Time:* {$appointmentDetails['time']}\n";
        $message .= "*Location:* {$appointmentDetails['location']}\n\n";
        $message .= "Please reply with *CONFIRM* or *RESCHEDULE*";

        return $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
    }
}

/**
 * Team collaboration service example
 */
class TeamCollaborationService
{
    private $whatsapp;
    private $sessionId;
    private $teamGroupId;

    public function __construct($whatsapp, $teamGroupId, $sessionId = 'team-bot')
    {
        $this->whatsapp = $whatsapp;
        $this->teamGroupId = $teamGroupId;
        $this->sessionId = $sessionId;
    }

    public function sendDailyStandup($standupData)
    {
        $message = "🌅 *Daily Standup - " . date('Y-m-d') . "*\n\n";
        
        foreach ($standupData as $member => $update) {
            $message .= "*{$member}:*\n";
            $message .= "• Yesterday: {$update['yesterday']}\n";
            $message .= "• Today: {$update['today']}\n";
            if (!empty($update['blockers'])) {
                $message .= "• Blockers: {$update['blockers']}\n";
            }
            $message .= "\n";
        }

        return $this->whatsapp->sendTextMessage($this->teamGroupId, $message, [], $this->sessionId);
    }

    public function sendAlert($alertType, $message, $severity = 'medium')
    {
        $emoji = match ($severity) {
            'low' => '💡',
            'medium' => '⚠️',
            'high' => '🚨',
            'critical' => '🔥',
            default => 'ℹ️'
        };

        $alertMessage = "{$emoji} *{$alertType} Alert*\n\n";
        $alertMessage .= $message . "\n\n";
        $alertMessage .= "_Sent at " . date('Y-m-d H:i:s') . "_";

        return $this->whatsapp->sendTextMessage($this->teamGroupId, $alertMessage, [], $this->sessionId);
    }

    public function sendCodeReviewRequest($prDetails)
    {
        $message = "👀 *Code Review Request*\n\n";
        $message .= "*PR:* {$prDetails['title']}\n";
        $message .= "*Author:* {$prDetails['author']}\n";
        $message .= "*Changes:* +{$prDetails['additions']} -{$prDetails['deletions']}\n";
        $message .= "*Link:* {$prDetails['url']}\n\n";
        $message .= "Please review when you have a moment! 🙏";

        return $this->whatsapp->sendTextMessage($this->teamGroupId, $message, [], $this->sessionId);
    }
}

/**
 * Marketing automation service example
 */
class MarketingAutomationService
{
    private $whatsapp;
    private $sessionId;

    public function __construct($whatsapp, $sessionId = 'marketing')
    {
        $this->whatsapp = $whatsapp;
        $this->sessionId = $sessionId;
    }

    public function sendWelcomeMessage($customerPhone, $customerName)
    {
        $chatId = $this->whatsapp->formatToWhatsAppId($customerPhone);
        
        $message = "🎉 Welcome to our service, {$customerName}!\n\n";
        $message .= "We're excited to have you on board. Here's what you can expect:\n\n";
        $message .= "• Regular updates about our products\n";
        $message .= "• Exclusive offers and discounts\n";
        $message .= "• Quick customer support\n\n";
        $message .= "Reply *HELP* anytime for assistance!";

        return $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
    }

    public function sendPromotionalCampaign($customerList, $campaignData)
    {
        $results = [];
        
        foreach ($customerList as $customer) {
            $chatId = $this->whatsapp->formatToWhatsAppId($customer['phone']);
            
            $message = "🎁 *Special Offer for {$customer['name']}!*\n\n";
            $message .= $campaignData['message'] . "\n\n";
            $message .= "*Discount Code:* `{$campaignData['code']}`\n";
            $message .= "*Valid until:* {$campaignData['expiry']}\n\n";
            $message .= "Shop now: {$campaignData['link']}";

            $response = $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
            $results[$customer['phone']] = $response->isSuccessful();
            
            // Rate limiting - small delay between messages
            usleep(500000); // 0.5 seconds
        }
        
        return $results;
    }
}

// Integration examples

try {
    echo "WhatsApp Integration Patterns Examples\n";
    echo "======================================\n\n";

    // Get the WhatsApp client component
    $whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;
    $sessionId = 'integration-demo';

    // Check if session is available
    $status = $whatsapp->getSessionStatus($sessionId);
    if (!$status->isSuccessful() || $status->get('state') !== 'CONNECTED') {
        echo "Please ensure your WhatsApp session is connected first.\n";
        echo "Session ID: $sessionId\n";
        echo "Run the session-management.php example to set up a session.\n\n";
        exit(1);
    }

    echo "Using session: $sessionId (Status: CONNECTED)\n\n";

    // 1. Customer Service Integration Example
    echo "1. Customer Service Integration Example\n";
    echo "=======================================\n\n";

    $customerService = new CustomerNotificationService($whatsapp, $sessionId);

    // Simulate order confirmation
    $orderDetails = [
        'id' => 'ORD-' . rand(1000, 9999),
        'total' => '99.99',
        'status' => 'Confirmed'
    ];

    echo "Sending order confirmation...\n";
    // Note: Replace with actual customer phone
    $testPhone = '1234567890'; // Replace with real phone number
    
    if ($testPhone === '1234567890') {
        echo "Please set a real phone number to test customer notifications.\n\n";
    } else {
        $response = $customerService->sendOrderConfirmation($testPhone, $orderDetails);
        if ($response->isSuccessful()) {
            echo "✓ Order confirmation sent!\n";
        }
    }

    // 2. Team Collaboration Example
    echo "2. Team Collaboration Integration Example\n";
    echo "=========================================\n\n";

    // Note: Replace with actual team group ID
    $teamGroupId = '123456789-123456789@g.us'; // Replace with real group ID
    
    if ($teamGroupId === '123456789-123456789@g.us') {
        echo "Please set a real team group ID to test collaboration features.\n\n";
    } else {
        $teamService = new TeamCollaborationService($whatsapp, $teamGroupId, $sessionId);

        // Send daily standup
        $standupData = [
            'Alice' => [
                'yesterday' => 'Completed user authentication module',
                'today' => 'Working on API documentation',
                'blockers' => ''
            ],
            'Bob' => [
                'yesterday' => 'Fixed payment gateway issues',
                'today' => 'Implementing order tracking',
                'blockers' => 'Waiting for API keys from vendor'
            ]
        ];

        echo "Sending daily standup to team...\n";
        $response = $teamService->sendDailyStandup($standupData);
        if ($response->isSuccessful()) {
            echo "✓ Daily standup sent!\n";
        }

        // Send alert
        echo "Sending system alert...\n";
        $alertResponse = $teamService->sendAlert(
            'System Performance',
            'Database response time is above normal threshold (>500ms). Please investigate.',
            'medium'
        );
        if ($alertResponse->isSuccessful()) {
            echo "✓ Alert sent!\n";
        }
    }

    // 3. Marketing Automation Example
    echo "3. Marketing Automation Integration Example\n";
    echo "===========================================\n\n";

    $marketingService = new MarketingAutomationService($whatsapp, $sessionId);

    // Welcome message example
    echo "Sending welcome message...\n";
    if ($testPhone !== '1234567890') {
        $response = $marketingService->sendWelcomeMessage($testPhone, 'John Doe');
        if ($response->isSuccessful()) {
            echo "✓ Welcome message sent!\n";
        }
    }

    // 4. Error Handling and Resilience Patterns
    echo "4. Error Handling and Resilience Patterns\n";
    echo "==========================================\n\n";

    echo "Testing error handling patterns...\n";

    // Retry pattern example
    function sendWithRetry($whatsapp, $chatId, $message, $maxRetries = 3, $sessionId = null)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $response = $whatsapp->sendTextMessage($chatId, $message, [], $sessionId);
                
                if ($response->isSuccessful()) {
                    echo "✓ Message sent successfully on attempt " . ($attempt + 1) . "\n";
                    return $response;
                }
                
                echo "⚠ Attempt " . ($attempt + 1) . " failed: " . $response->getErrorMessage() . "\n";
                
            } catch (WhatsAppException $e) {
                echo "⚠ Attempt " . ($attempt + 1) . " failed with exception: " . $e->getMessage() . "\n";
            }
            
            $attempt++;
            
            if ($attempt < $maxRetries) {
                echo "Retrying in 2 seconds...\n";
                sleep(2);
            }
        }
        
        echo "✗ All retry attempts failed\n";
        return null;
    }

    // Test with invalid chat ID to demonstrate retry
    echo "Testing retry pattern with invalid chat ID...\n";
    sendWithRetry($whatsapp, 'invalid-chat-id', 'Test message', 2, $sessionId);

    echo "\n";

    // 5. Rate Limiting Example
    echo "5. Rate Limiting Example\n";
    echo "========================\n\n";

    class RateLimitedWhatsAppService
    {
        private $whatsapp;
        private $sessionId;
        private $lastMessageTime = 0;
        private $minInterval = 1; // Minimum 1 second between messages

        public function __construct($whatsapp, $sessionId)
        {
            $this->whatsapp = $whatsapp;
            $this->sessionId = $sessionId;
        }

        public function sendMessage($chatId, $message)
        {
            $now = time();
            $timeSinceLastMessage = $now - $this->lastMessageTime;
            
            if ($timeSinceLastMessage < $this->minInterval) {
                $sleepTime = $this->minInterval - $timeSinceLastMessage;
                echo "Rate limiting: sleeping for {$sleepTime} seconds...\n";
                sleep($sleepTime);
            }
            
            $response = $this->whatsapp->sendTextMessage($chatId, $message, [], $this->sessionId);
            $this->lastMessageTime = time();
            
            return $response;
        }
    }

    echo "Rate limiting prevents sending messages too quickly.\n";
    echo "This helps avoid being flagged by WhatsApp.\n\n";

    // 6. Configuration Management Example
    echo "6. Configuration Management Example\n";
    echo "===================================\n\n";

    // Example of environment-specific configuration
    $config = [
        'development' => [
            'enableLogging' => true,
            'enableRetries' => true,
            'maxRetries' => 2,
            'rateLimitInterval' => 0.5,
        ],
        'production' => [
            'enableLogging' => false,
            'enableRetries' => true,
            'maxRetries' => 5,
            'rateLimitInterval' => 1.0,
        ]
    ];

    $environment = YII_ENV; // 'dev', 'test', 'prod'
    $envConfig = $config[YII_ENV === 'prod' ? 'production' : 'development'];

    echo "Current environment: $environment\n";
    echo "Configuration:\n";
    foreach ($envConfig as $key => $value) {
        echo "  $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }

    echo "\n✓ Integration patterns examples completed!\n\n";

    // Best practices summary
    echo "Integration Best Practices:\n";
    echo "===========================\n";
    echo "• Use dependency injection for the WhatsApp client\n";
    echo "• Implement proper error handling and retries\n";
    echo "• Add rate limiting to avoid being blocked\n";
    echo "• Use environment-specific configuration\n";
    echo "• Log important operations for debugging\n";
    echo "• Validate input data before sending\n";
    echo "• Handle session disconnections gracefully\n";
    echo "• Use meaningful session IDs for different use cases\n";
    echo "• Implement proper message queuing for high volume\n";
    echo "• Monitor API usage and performance\n";

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}