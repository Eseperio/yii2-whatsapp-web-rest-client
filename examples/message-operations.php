<?php
/**
 * Message operations examples
 * 
 * This file demonstrates various message operations with the WhatsApp Web REST client
 */

use eseperio\whatsapp\exceptions\WhatsAppException;

// Get the WhatsApp client component
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

try {
    $sessionId = 'demo-session';
    
    // Make sure session is connected
    $status = $whatsapp->getSessionStatus($sessionId);
    if (!$status->isSuccessful() || $status->get('state') !== 'CONNECTED') {
        echo "Please ensure your WhatsApp session is connected first.\n";
        exit(1);
    }

    echo "WhatsApp Message Operations Examples\n";
    echo "====================================\n\n";

    // You'll need to replace this with an actual chat ID
    $testChatId = '1234567890@c.us'; // Replace with real chat ID
    
    echo "NOTE: Please update \$testChatId with a real chat ID to test messaging.\n";
    echo "You can get chat IDs from the basic-usage.php example.\n\n";
    
    if ($testChatId === '1234567890@c.us') {
        echo "Skipping message operations - please set a real chat ID.\n";
        exit(0);
    }

    // 1. Send different types of messages
    echo "1. Sending different types of messages...\n\n";

    // Text message
    echo "   • Sending text message...\n";
    $textResponse = $whatsapp->sendTextMessage(
        $testChatId,
        "Hello! This is a test message from the Yii2 WhatsApp client. 📱",
        [],
        $sessionId
    );
    
    if ($textResponse->isSuccessful()) {
        $textMessageId = $textResponse->get('id');
        echo "     ✓ Text message sent! ID: $textMessageId\n";
    }

    sleep(1); // Small delay between messages

    // Location message
    echo "   • Sending location message...\n";
    $locationResponse = $whatsapp->sendLocationMessage(
        $testChatId,
        -6.2088, // Jakarta latitude
        106.8456, // Jakarta longitude
        'Jakarta, Indonesia 📍',
        [],
        $sessionId
    );
    
    if ($locationResponse->isSuccessful()) {
        echo "     ✓ Location message sent!\n";
    }

    sleep(1);

    // Poll message
    echo "   • Sending poll message...\n";
    $pollResponse = $whatsapp->sendPollMessage(
        $testChatId,
        'What is your favorite programming language? 🤔',
        ['PHP', 'JavaScript', 'Python', 'Java'],
        ['allowMultipleAnswers' => false],
        [],
        $sessionId
    );
    
    if ($pollResponse->isSuccessful()) {
        echo "     ✓ Poll message sent!\n";
    }

    sleep(1);

    // Media from URL
    echo "   • Sending media from URL...\n";
    $mediaResponse = $whatsapp->sendMediaFromUrl(
        $testChatId,
        'https://via.placeholder.com/300x200.png?text=Yii2+WhatsApp+Client',
        ['caption' => 'This is a test image from URL 🖼️'],
        $sessionId
    );
    
    if ($mediaResponse->isSuccessful()) {
        $mediaMessageId = $mediaResponse->get('id');
        echo "     ✓ Media message sent! ID: $mediaMessageId\n";
    }

    echo "\n";

    // 2. Message interactions
    if (isset($textMessageId)) {
        echo "2. Message interactions...\n\n";

        // React to message
        echo "   • Adding reaction to text message...\n";
        $reactionResponse = $whatsapp->reactToMessage(
            $testChatId,
            $textMessageId,
            '👍',
            $sessionId
        );
        
        if ($reactionResponse->isSuccessful()) {
            echo "     ✓ Reaction added!\n";
        }

        sleep(1);

        // Reply to message
        echo "   • Replying to text message...\n";
        $replyResponse = $whatsapp->replyToMessage(
            $testChatId,
            $textMessageId,
            'string',
            'This is a reply to your message! 💬',
            [],
            $sessionId
        );
        
        if ($replyResponse->isSuccessful()) {
            $replyMessageId = $replyResponse->get('id');
            echo "     ✓ Reply sent! ID: $replyMessageId\n";
        }

        sleep(1);

        // Get message info
        echo "   • Getting message information...\n";
        $infoResponse = $whatsapp->getMessageInfo($testChatId, $textMessageId, $sessionId);
        
        if ($infoResponse->isSuccessful()) {
            $info = $infoResponse->getResult();
            echo "     ✓ Message info retrieved:\n";
            echo "       - Delivery status: " . ($info['delivery'] ?? 'Unknown') . "\n";
            echo "       - Read status: " . ($info['read'] ?? 'Unknown') . "\n";
        }

        echo "\n";
    }

    // 3. Chat state indicators
    echo "3. Chat state indicators...\n\n";

    echo "   • Sending typing indicator...\n";
    $typingResponse = $whatsapp->sendTyping($testChatId, $sessionId);
    if ($typingResponse->isSuccessful()) {
        echo "     ✓ Typing indicator sent (will last ~25 seconds)\n";
    }

    sleep(2);

    echo "   • Clearing chat state...\n";
    $clearResponse = $whatsapp->clearChatState($testChatId, $sessionId);
    if ($clearResponse->isSuccessful()) {
        echo "     ✓ Chat state cleared\n";
    }

    sleep(1);

    echo "   • Sending recording indicator...\n";
    $recordingResponse = $whatsapp->sendRecording($testChatId, $sessionId);
    if ($recordingResponse->isSuccessful()) {
        echo "     ✓ Recording indicator sent\n";
    }

    sleep(2);

    echo "   • Clearing recording state...\n";
    $whatsapp->clearChatState($testChatId, $sessionId);

    echo "\n";

    // 4. Mark as seen
    echo "4. Marking chat as seen...\n";
    $seenResponse = $whatsapp->markChatAsSeen($testChatId, $sessionId);
    if ($seenResponse->isSuccessful()) {
        echo "   ✓ Chat marked as seen\n";
    }

    echo "\n";

    // 5. Search messages
    echo "5. Searching messages...\n";
    $searchResponse = $whatsapp->searchMessages(
        'test',
        ['limit' => 10],
        $sessionId
    );
    
    if ($searchResponse->isSuccessful()) {
        $results = $searchResponse->getResult();
        echo "   ✓ Found " . count($results) . " messages containing 'test'\n";
        
        foreach (array_slice($results, 0, 3) as $message) {
            $preview = substr($message['body'] ?? '', 0, 50);
            echo "     - \"$preview...\"\n";
        }
    }

    echo "\n";

    // 6. Advanced message operations (be careful with these!)
    if (isset($replyMessageId)) {
        echo "6. Advanced operations (use with caution)...\n\n";

        echo "   • Demonstrating message deletion...\n";
        echo "     (Waiting 5 seconds before deleting the reply message)\n";
        
        sleep(5);
        
        $deleteResponse = $whatsapp->deleteMessage(
            $testChatId,
            $replyMessageId,
            true, // Delete for everyone
            false, // Don't clear media
            $sessionId
        );
        
        if ($deleteResponse->isSuccessful()) {
            echo "     ✓ Reply message deleted for everyone\n";
        }
    }

    echo "\n✓ Message operations examples completed!\n";

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}