<?php
/**
 * Group management examples
 * 
 * This file demonstrates group operations with the WhatsApp Web REST client
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

    echo "WhatsApp Group Management Examples\n";
    echo "==================================\n\n";

    // 1. Create a new group
    echo "1. Creating a new group...\n";
    
    // You'll need to replace these with actual contact IDs
    $participants = [
        // '1234567890@c.us',  // Add real contact IDs here
        // '0987654321@c.us',
    ];
    
    if (empty($participants)) {
        echo "Please add some participant contact IDs to create a group.\n";
        echo "You can get contact IDs from the basic-usage.php example.\n\n";
    } else {
        $groupResponse = $whatsapp->createGroup(
            'Test Group from Yii2',
            $participants,
            [],
            $sessionId
        );
        
        if ($groupResponse->isSuccessful()) {
            $groupId = $groupResponse->get('gid');
            echo "✓ Group created successfully!\n";
            echo "Group ID: $groupId\n\n";
            
            // 2. Set group description
            echo "2. Setting group description...\n";
            $descResponse = $whatsapp->setGroupDescription(
                $groupId,
                'This is a test group created by the Yii2 WhatsApp client library.',
                $sessionId
            );
            
            if ($descResponse->isSuccessful()) {
                echo "✓ Group description set successfully!\n\n";
            }
            
            // 3. Get group invite code
            echo "3. Getting group invite code...\n";
            $inviteResponse = $whatsapp->getGroupInviteCode($groupId, $sessionId);
            
            if ($inviteResponse->isSuccessful()) {
                $inviteCode = $inviteResponse->getResult();
                echo "✓ Group invite code: $inviteCode\n";
                echo "Invite link: https://chat.whatsapp.com/$inviteCode\n\n";
            }
            
            // 4. Add more participants (if you have more contact IDs)
            $newParticipants = [
                // '1111111111@c.us',  // Add more contact IDs here
            ];
            
            if (!empty($newParticipants)) {
                echo "4. Adding new participants...\n";
                $addResponse = $whatsapp->addGroupParticipants(
                    $groupId,
                    $newParticipants,
                    ['sleep' => [250, 500]], // Sleep between additions
                    $sessionId
                );
                
                if ($addResponse->isSuccessful()) {
                    echo "✓ Participants added successfully!\n\n";
                }
            }
            
            // 5. Promote a participant to admin
            if (!empty($participants)) {
                echo "5. Promoting participant to admin...\n";
                $promoteResponse = $whatsapp->promoteGroupParticipants(
                    $groupId,
                    [$participants[0]], // Promote first participant
                    $sessionId
                );
                
                if ($promoteResponse->isSuccessful()) {
                    echo "✓ Participant promoted to admin!\n\n";
                }
            }
            
            // 6. Send a message to the group
            echo "6. Sending welcome message to group...\n";
            $messageResponse = $whatsapp->sendTextMessage(
                $groupId,
                "Welcome to our test group! 🎉\n\nThis group was created using the Yii2 WhatsApp Web REST client.",
                [],
                $sessionId
            );
            
            if ($messageResponse->isSuccessful()) {
                echo "✓ Welcome message sent!\n";
                echo "Message ID: " . $messageResponse->get('id') . "\n\n";
            }
            
            // 7. Update group settings
            echo "7. Updating group settings...\n";
            
            // Only admins can send messages
            $settingsResponse = $whatsapp->makeRequest(
                'POST',
                '/groupChat/setMessagesAdminsOnly/{sessionId}',
                [
                    'chatId' => $groupId,
                    'adminsOnly' => true
                ],
                $sessionId
            );
            
            if ($settingsResponse->isSuccessful()) {
                echo "✓ Group settings updated - only admins can send messages!\n\n";
            }
            
            echo "Group management examples completed!\n";
            echo "Group ID for future reference: $groupId\n";
            
        } else {
            echo "✗ Failed to create group: " . $groupResponse->getErrorMessage() . "\n";
        }
    }

    // Additional group operations (for existing groups)
    echo "\nAdditional Group Operations\n";
    echo "===========================\n\n";
    
    // Get all chats (including groups)
    echo "Retrieving all chats to find groups...\n";
    $chatsResponse = $whatsapp->getChats([], $sessionId);
    
    if ($chatsResponse->isSuccessful()) {
        $chats = $chatsResponse->getResult();
        $groups = array_filter($chats, function($chat) {
            return isset($chat['isGroup']) && $chat['isGroup'];
        });
        
        echo "Found " . count($groups) . " groups:\n";
        
        foreach (array_slice($groups, 0, 5) as $group) {
            echo "- " . $group['name'] . " (ID: " . $group['id']['_serialized'] . ")\n";
            echo "  Participants: " . count($group['participants'] ?? []) . "\n";
            echo "  Description: " . substr($group['description'] ?? 'No description', 0, 50) . "\n\n";
        }
    }

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}