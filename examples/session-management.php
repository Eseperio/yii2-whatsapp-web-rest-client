<?php
/**
 * Session management examples
 * 
 * This file demonstrates comprehensive session management
 */

use eseperio\whatsapp\exceptions\WhatsAppException;

// Get the WhatsApp client component
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

try {
    echo "WhatsApp Session Management Examples\n";
    echo "====================================\n\n";

    // 1. Health check
    echo "1. Performing health check...\n";
    $pingResponse = $whatsapp->ping();
    
    if ($pingResponse->isSuccessful()) {
        echo "   ✓ API is responsive: " . $pingResponse->get('message') . "\n\n";
    } else {
        echo "   ✗ API is not responding!\n";
        exit(1);
    }

    // 2. List all existing sessions
    echo "2. Listing all existing sessions...\n";
    $sessionsResponse = $whatsapp->getSessions();
    
    if ($sessionsResponse->isSuccessful()) {
        $sessions = $sessionsResponse->getResult();
        echo "   Found " . count($sessions) . " existing sessions:\n";
        
        foreach ($sessions as $session) {
            echo "   - $session\n";
            
            // Check status of each session
            $status = $whatsapp->getSessionStatus($session);
            if ($status->isSuccessful()) {
                echo "     State: " . $status->get('state') . "\n";
            }
        }
        echo "\n";
    }

    // 3. Create a new session
    $newSessionId = 'test-session-' . time();
    echo "3. Creating new session: $newSessionId\n";
    
    $startResponse = $whatsapp->startSession($newSessionId);
    
    if ($startResponse->isSuccessful()) {
        echo "   ✓ Session started successfully!\n";
        echo "   Message: " . $startResponse->get('message') . "\n\n";
    } else {
        echo "   ✗ Failed to start session: " . $startResponse->getErrorMessage() . "\n\n";
    }

    // 4. Monitor session state changes
    echo "4. Monitoring session state...\n";
    
    $maxAttempts = 10;
    $attempt = 0;
    $previousState = null;
    
    while ($attempt < $maxAttempts) {
        $statusResponse = $whatsapp->getSessionStatus($newSessionId);
        
        if ($statusResponse->isSuccessful()) {
            $currentState = $statusResponse->get('state');
            $message = $statusResponse->get('message', '');
            
            // Only print if state changed
            if ($currentState !== $previousState) {
                echo "   State changed: $currentState";
                if (!empty($message)) {
                    echo " ($message)";
                }
                echo "\n";
                $previousState = $currentState;
            }
            
            // If we reach CONNECTED state, we're done
            if ($currentState === 'CONNECTED') {
                echo "   ✓ Session is now connected!\n\n";
                break;
            }
            
            // If we need QR code authentication
            if ($currentState === 'UNPAIRED' || $currentState === 'QRCODE') {
                echo "\n   Getting QR code for authentication...\n";
                
                $qrResponse = $whatsapp->getSessionQr($newSessionId);
                if ($qrResponse->isSuccessful()) {
                    echo "   Please scan this QR code with your WhatsApp mobile app:\n";
                    echo "   " . $qrResponse->get('qr') . "\n\n";
                    
                    // You can also get QR as image
                    echo "   Or visit this URL to see QR as image:\n";
                    echo "   " . $whatsapp->baseUrl . "/session/qr/$newSessionId/image\n\n";
                }
            }
        }
        
        sleep(2);
        $attempt++;
    }

    if ($attempt >= $maxAttempts) {
        echo "   ⚠ Session monitoring timeout. Current state: $previousState\n\n";
    }

    // 5. Demonstrate session operations based on current state
    $finalStatus = $whatsapp->getSessionStatus($newSessionId);
    
    if ($finalStatus->isSuccessful()) {
        $state = $finalStatus->get('state');
        
        echo "5. Session operations for state: $state\n";
        
        switch ($state) {
            case 'CONNECTED':
                echo "   Session is connected! You can now:\n";
                echo "   - Send messages\n";
                echo "   - Get contacts and chats\n";
                echo "   - Perform all WhatsApp operations\n\n";
                
                // Demonstrate some basic operations
                echo "   Testing basic operations...\n";
                
                $contactsResponse = $whatsapp->getContacts($newSessionId);
                if ($contactsResponse->isSuccessful()) {
                    $contacts = $contactsResponse->getResult();
                    echo "   ✓ Retrieved " . count($contacts) . " contacts\n";
                }
                
                $chatsResponse = $whatsapp->getChats([], $newSessionId);
                if ($chatsResponse->isSuccessful()) {
                    $chats = $chatsResponse->getResult();
                    echo "   ✓ Retrieved " . count($chats) . " chats\n";
                }
                
                $infoResponse = $whatsapp->getClientInfo($newSessionId);
                if ($infoResponse->isSuccessful()) {
                    echo "   ✓ Client info retrieved\n";
                }
                break;
                
            case 'UNPAIRED':
            case 'QRCODE':
                echo "   Session needs authentication:\n";
                echo "   - Scan the QR code shown above\n";
                echo "   - Or use pairing code method\n\n";
                break;
                
            case 'STARTING':
                echo "   Session is still starting up\n";
                echo "   - Wait a bit longer\n";
                echo "   - Check status again\n\n";
                break;
                
            default:
                echo "   Session is in state: $state\n";
                echo "   - Check documentation for this state\n\n";
                break;
        }
    }

    // 6. Session restart example
    echo "6. Session restart example...\n";
    echo "   Restarting session: $newSessionId\n";
    
    $restartResponse = $whatsapp->restartSession($newSessionId);
    if ($restartResponse->isSuccessful()) {
        echo "   ✓ Session restart initiated\n";
        echo "   Message: " . $restartResponse->get('message') . "\n\n";
    }

    // 7. Session termination options
    echo "7. Session termination options...\n";
    echo "   Note: This will terminate the test session we created\n";
    
    // Option 1: Stop session (logout but keep session data)
    echo "   Stopping session (logout)...\n";
    $stopResponse = $whatsapp->stopSession($newSessionId);
    if ($stopResponse->isSuccessful()) {
        echo "   ✓ Session stopped: " . $stopResponse->get('message') . "\n";
    }
    
    sleep(1);
    
    // Option 2: Terminate session (completely remove)
    echo "   Terminating session (remove completely)...\n";
    $terminateResponse = $whatsapp->terminateSession($newSessionId);
    if ($terminateResponse->isSuccessful()) {
        echo "   ✓ Session terminated: " . $terminateResponse->get('message') . "\n";
    }

    echo "\n";

    // 8. Verify session is gone
    echo "8. Verifying session removal...\n";
    $finalSessionsResponse = $whatsapp->getSessions();
    
    if ($finalSessionsResponse->isSuccessful()) {
        $finalSessions = $finalSessionsResponse->getResult();
        
        if (!in_array($newSessionId, $finalSessions)) {
            echo "   ✓ Session successfully removed\n";
        } else {
            echo "   ⚠ Session still exists in the list\n";
        }
    }

    echo "\n✓ Session management examples completed!\n";

    // 9. Best practices summary
    echo "\nSession Management Best Practices:\n";
    echo "==================================\n";
    echo "• Always check session state before performing operations\n";
    echo "• Handle QR code authentication gracefully\n";
    echo "• Use unique session IDs for different use cases\n";
    echo "• Properly terminate sessions when done\n";
    echo "• Monitor session health regularly\n";
    echo "• Handle session disconnections and reconnections\n";
    echo "• Keep session credentials secure\n";

} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}