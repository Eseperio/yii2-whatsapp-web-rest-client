<?php
/**
 * Basic WhatsApp usage examples
 * 
 * This file demonstrates basic operations with the WhatsApp Web REST client
 */

use eseperio\whatsapp\exceptions\WhatsAppException;

// Get the WhatsApp client component
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

try {
    // 1. Start a session
    echo "Starting WhatsApp session...\n";
    $response = $whatsapp->startSession('demo-session');
    
    if ($response->isSuccessful()) {
        echo "✓ Session started successfully\n";
    } else {
        echo "✗ Failed to start session: " . $response->getErrorMessage() . "\n";
        exit(1);
    }

    // 2. Get QR code for authentication
    echo "\nGetting QR code for authentication...\n";
    $qrResponse = $whatsapp->getSessionQr('demo-session');
    
    if ($qrResponse->isSuccessful()) {
        echo "Please scan this QR code with your WhatsApp mobile app:\n";
        echo $qrResponse->get('qr') . "\n";
    }

    // 3. Wait for authentication and check status
    echo "\nWaiting for authentication...\n";
    $attempts = 0;
    $maxAttempts = 30;
    
    while ($attempts < $maxAttempts) {
        sleep(2);
        $status = $whatsapp->getSessionStatus('demo-session');
        
        if ($status->isSuccessful()) {
            $state = $status->get('state');
            echo "Session state: $state\n";
            
            if ($state === 'CONNECTED') {
                echo "✓ Successfully authenticated!\n";
                break;
            }
        }
        
        $attempts++;
    }

    if ($attempts >= $maxAttempts) {
        echo "✗ Authentication timeout\n";
        exit(1);
    }

    // 4. Get contacts
    echo "\nRetrieving contacts...\n";
    $contactsResponse = $whatsapp->getContacts('demo-session');
    
    if ($contactsResponse->isSuccessful()) {
        $contacts = $contactsResponse->getResult();
        echo "Found " . count($contacts) . " contacts\n";
        
        // Show first 5 contacts
        foreach (array_slice($contacts, 0, 5) as $contact) {
            echo "- " . ($contact['name'] ?? $contact['number']) . " (" . $contact['id']['user'] . ")\n";
        }
    }

    // 5. Get chats
    echo "\nRetrieving chats...\n";
    $chatsResponse = $whatsapp->getChats([], 'demo-session');
    
    if ($chatsResponse->isSuccessful()) {
        $chats = $chatsResponse->getResult();
        echo "Found " . count($chats) . " chats\n";
        
        // Show first 5 chats
        foreach (array_slice($chats, 0, 5) as $chat) {
            $lastMessage = $chat['lastMessage'] ?? null;
            $lastText = $lastMessage ? substr($lastMessage['body'], 0, 50) . '...' : 'No messages';
            echo "- " . ($chat['name'] ?? 'Unknown') . ": $lastText\n";
        }
    }

    // 6. Send a test message (uncomment and modify the chat ID)
    /*
    $testChatId = '1234567890@c.us'; // Replace with actual chat ID
    echo "\nSending test message...\n";
    
    $messageResponse = $whatsapp->sendTextMessage(
        $testChatId,
        'Hello from Yii2 WhatsApp client! 🚀',
        [],
        'demo-session'
    );
    
    if ($messageResponse->isSuccessful()) {
        echo "✓ Message sent successfully!\n";
        echo "Message ID: " . $messageResponse->get('id') . "\n";
    } else {
        echo "✗ Failed to send message: " . $messageResponse->getErrorMessage() . "\n";
    }
    */

    echo "\n✓ Basic operations completed successfully!\n";

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}