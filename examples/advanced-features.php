<?php
/**
 * Advanced features examples
 * 
 * This file demonstrates advanced features and helper methods
 */

use eseperio\whatsapp\exceptions\WhatsAppException;

// Get the WhatsApp client component
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

try {
    $sessionId = 'demo-session';
    
    echo "WhatsApp Advanced Features Examples\n";
    echo "===================================\n\n";

    // 1. Validation examples
    echo "1. Input validation examples...\n\n";

    // Phone number validation
    $testNumbers = [
        '+1234567890',
        '1234567890',
        '+62-812-3456-7890',
        '123', // Invalid - too short
        '12345678901234567', // Invalid - too long
    ];

    foreach ($testNumbers as $number) {
        $isValid = $whatsapp->isValidWhatsAppNumber($number);
        $formatted = $whatsapp->formatToWhatsAppId($number);
        echo "   Number: $number\n";
        echo "   Valid: " . ($isValid ? "✓" : "✗") . "\n";
        echo "   Formatted: $formatted\n\n";
    }

    // Chat ID validation
    $testChatIds = [
        '1234567890@c.us',  // Individual
        '123456789-123456@g.us',  // Group
        '1234567890@s.whatsapp.net',  // Status
    ];

    foreach ($testChatIds as $chatId) {
        echo "   Chat ID: $chatId\n";
        echo "   Type: ";
        if ($whatsapp->isGroupChat($chatId)) {
            echo "Group\n";
        } elseif ($whatsapp->isIndividualChat($chatId)) {
            echo "Individual\n";
        } else {
            echo "Other\n";
        }
        echo "   Number: " . $whatsapp->extractNumberFromId($chatId) . "\n\n";
    }

    // URL validation
    $testUrls = [
        'https://example.com/image.jpg',
        'http://example.com/video.mp4',
        'ftp://files.example.com/doc.pdf',
        'not-a-url',
        '',
    ];

    foreach ($testUrls as $url) {
        $isValid = $whatsapp->isValidUrl($url);
        echo "   URL: $url\n";
        echo "   Valid: " . ($isValid ? "✓" : "✗") . "\n\n";
    }

    // Coordinates validation
    $testCoordinates = [
        [-6.2088, 106.8456], // Jakarta - valid
        [0, 0], // Valid
        [91, 0], // Invalid latitude
        [0, 181], // Invalid longitude
        [-91, -181], // Both invalid
    ];

    foreach ($testCoordinates as $coords) {
        [$lat, $lng] = $coords;
        $isValid = $whatsapp->isValidCoordinates($lat, $lng);
        echo "   Coordinates: [$lat, $lng]\n";
        echo "   Valid: " . ($isValid ? "✓" : "✗") . "\n\n";
    }

    // 2. Message helper examples
    echo "2. Message helper examples...\n\n";

    // NOTE: Set a real chat ID to test these
    $testChatId = '1234567890@c.us'; // Replace with real chat ID

    if ($testChatId === '1234567890@c.us') {
        echo "   Please set a real chat ID to test message helpers.\n\n";
    } else {
        // Make sure session is connected
        $status = $whatsapp->getSessionStatus($sessionId);
        if ($status->isSuccessful() && $status->get('state') === 'CONNECTED') {
            
            // Formatted text examples
            echo "   • Sending formatted text messages...\n";
            
            $whatsapp->sendFormattedText(
                $testChatId,
                'This is bold text',
                true, // bold
                false, // italic
                false, // monospace
                [],
                $sessionId
            );
            
            sleep(1);
            
            $whatsapp->sendFormattedText(
                $testChatId,
                'This is italic text',
                false, // bold
                true, // italic
                false, // monospace
                [],
                $sessionId
            );
            
            sleep(1);
            
            $whatsapp->sendFormattedText(
                $testChatId,
                'This is monospace text',
                false, // bold
                false, // italic
                true, // monospace
                [],
                $sessionId
            );
            
            echo "     ✓ Formatted messages sent!\n\n";
            
            // Send with typing
            echo "   • Sending message with typing indicator...\n";
            $typingResponse = $whatsapp->sendWithTyping(
                $testChatId,
                'string',
                'This message was sent with a typing indicator! ⌨️',
                3, // 3 seconds of typing
                [],
                $sessionId
            );
            
            if ($typingResponse->isSuccessful()) {
                echo "     ✓ Message sent with typing!\n";
                $messageId = $typingResponse->get('id');
                
                // Quick reaction
                echo "   • Adding quick reaction...\n";
                $reactionResponse = $whatsapp->quickReact($testChatId, $messageId, '👍', $sessionId);
                if ($reactionResponse->isSuccessful()) {
                    echo "     ✓ Reaction added!\n";
                }
            }
            
            echo "\n";
            
            // Broadcast example
            echo "   • Broadcast message example...\n";
            $broadcastChats = [$testChatId]; // Add more chat IDs for real broadcast
            
            $broadcastResponses = $whatsapp->broadcastMessage(
                $broadcastChats,
                'string',
                'This is a broadcast message! 📢',
                [],
                $sessionId
            );
            
            $successCount = 0;
            foreach ($broadcastResponses as $chatId => $response) {
                if ($response->isSuccessful()) {
                    $successCount++;
                }
            }
            
            echo "     ✓ Broadcast sent to $successCount/" . count($broadcastChats) . " chats\n\n";
            
        } else {
            echo "   Session not connected. Please connect first.\n\n";
        }
    }

    // 3. Text sanitization examples
    echo "3. Text sanitization examples...\n\n";

    $testTexts = [
        "Normal text",
        "Text with\nnull\x00byte",
        "Text with control\x1Fcharacters",
        str_repeat("Very long text ", 500), // Very long text
        "  Text with extra spaces  ",
    ];

    foreach ($testTexts as $text) {
        $sanitized = $whatsapp->sanitizeMessageText($text, 100);
        echo "   Original length: " . strlen($text) . "\n";
        echo "   Sanitized length: " . strlen($sanitized) . "\n";
        echo "   Preview: " . substr($sanitized, 0, 50) . "...\n\n";
    }

    // 4. Media validation example
    echo "4. Media validation example...\n\n";

    // Valid media data
    $validMediaData = [
        'mimetype' => 'image/png',
        'data' => base64_encode('fake-image-data'),
        'filename' => 'test.png'
    ];

    // Invalid media data
    $invalidMediaData = [
        'mimetype' => 'image/png',
        // Missing 'data' field
    ];

    echo "   Valid media data: " . ($whatsapp->isValidMediaData($validMediaData) ? "✓" : "✗") . "\n";
    echo "   Invalid media data: " . ($whatsapp->isValidMediaData($invalidMediaData) ? "✓" : "✗") . "\n\n";

    // 5. Poll validation example
    echo "5. Poll validation example...\n\n";

    $validPollOptions = ['Option 1', 'Option 2', 'Option 3'];
    $invalidPollOptions = []; // Empty array
    $tooManyOptions = array_fill(0, 15, 'Option'); // Too many options

    echo "   Valid poll options: " . ($whatsapp->isValidPollOptions($validPollOptions) ? "✓" : "✗") . "\n";
    echo "   Invalid poll options (empty): " . ($whatsapp->isValidPollOptions($invalidPollOptions) ? "✓" : "✗") . "\n";
    echo "   Too many poll options: " . ($whatsapp->isValidPollOptions($tooManyOptions) ? "✓" : "✗") . "\n\n";

    echo "✓ Advanced features examples completed!\n";

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}