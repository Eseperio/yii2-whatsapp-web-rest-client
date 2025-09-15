<?php

namespace eseperio\whatsapp\commands;

use yii\console\Controller;
use yii\helpers\Console;
use eseperio\whatsapp\exceptions\WhatsAppException;

/**
 * WhatsApp Console Command
 * 
 * Provides console commands for managing WhatsApp sessions and operations
 * 
 * Usage:
 * php yii whatsapp/session/start session-id
 * php yii whatsapp/session/status session-id
 * php yii whatsapp/message/send session-id chat-id "Hello World"
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\commands
 */
class WhatsAppController extends Controller
{
    /**
     * @var string Default session ID
     */
    public $sessionId = 'default';

    /**
     * @var bool Enable verbose output
     */
    public $verbose = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['sessionId', 'verbose']);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            's' => 'sessionId',
            'v' => 'verbose',
        ]);
    }

    /**
     * Get WhatsApp client component
     * 
     * @return \eseperio\whatsapp\components\WhatsAppClient
     */
    protected function getWhatsAppClient()
    {
        return \Yii::$app->getModule('whatsapp')->whatsappClient;
    }

    /**
     * Start a WhatsApp session
     * 
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionSessionStart($sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        $this->stdout("Starting WhatsApp session: $sessionId\n", Console::FG_YELLOW);
        
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->startSession($sessionId);
            
            if ($response->isSuccessful()) {
                $this->stdout("✓ Session started successfully!\n", Console::FG_GREEN);
                $this->stdout("Message: " . $response->get('message') . "\n");
                
                // Get QR code for authentication
                $this->actionSessionQr($sessionId);
                
            } else {
                $this->stderr("✗ Failed to start session: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Get session status
     * 
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionSessionStatus($sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->getSessionStatus($sessionId);
            
            if ($response->isSuccessful()) {
                $state = $response->get('state');
                $message = $response->get('message', '');
                
                $color = match ($state) {
                    'CONNECTED' => Console::FG_GREEN,
                    'UNPAIRED', 'QRCODE' => Console::FG_YELLOW,
                    default => Console::FG_RED
                };
                
                $this->stdout("Session: $sessionId\n");
                $this->stdout("State: ", null, false);
                $this->stdout($state, $color);
                if (!empty($message)) {
                    $this->stdout(" ($message)");
                }
                $this->stdout("\n");
                
            } else {
                $this->stderr("✗ Failed to get session status: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Get QR code for session authentication
     * 
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionSessionQr($sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->getSessionQr($sessionId);
            
            if ($response->isSuccessful()) {
                $qr = $response->get('qr');
                
                $this->stdout("\nQR Code for session authentication:\n", Console::FG_CYAN);
                $this->stdout("Please scan this QR code with your WhatsApp mobile app:\n\n");
                $this->stdout($qr . "\n\n");
                $this->stdout("Or visit: " . $whatsapp->baseUrl . "/session/qr/$sessionId/image\n", Console::FG_BLUE);
                
            } else {
                $this->stderr("✗ Failed to get QR code: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Stop a WhatsApp session
     * 
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionSessionStop($sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        $this->stdout("Stopping WhatsApp session: $sessionId\n", Console::FG_YELLOW);
        
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->stopSession($sessionId);
            
            if ($response->isSuccessful()) {
                $this->stdout("✓ Session stopped successfully!\n", Console::FG_GREEN);
                $this->stdout("Message: " . $response->get('message') . "\n");
            } else {
                $this->stderr("✗ Failed to stop session: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * List all WhatsApp sessions
     */
    public function actionSessionList()
    {
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->getSessions();
            
            if ($response->isSuccessful()) {
                $sessions = $response->getResult();
                
                $this->stdout("Active WhatsApp sessions:\n", Console::FG_CYAN);
                
                if (empty($sessions)) {
                    $this->stdout("No active sessions found.\n");
                } else {
                    foreach ($sessions as $session) {
                        $this->stdout("• $session", null, false);
                        
                        // Get status for each session
                        $statusResponse = $whatsapp->getSessionStatus($session);
                        if ($statusResponse->isSuccessful()) {
                            $state = $statusResponse->get('state');
                            $color = match ($state) {
                                'CONNECTED' => Console::FG_GREEN,
                                'UNPAIRED', 'QRCODE' => Console::FG_YELLOW,
                                default => Console::FG_RED
                            };
                            $this->stdout(" ($state)", $color);
                        }
                        $this->stdout("\n");
                    }
                }
                
            } else {
                $this->stderr("✗ Failed to get sessions: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Send a text message
     * 
     * @param string $chatId Chat ID (phone number or group ID)
     * @param string $message Message text
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionMessageSend($chatId, $message, $sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        // Format chat ID if it's a phone number
        $whatsapp = $this->getWhatsAppClient();
        if ($whatsapp->isValidWhatsAppNumber($chatId)) {
            $chatId = $whatsapp->formatToWhatsAppId($chatId);
        }
        
        $this->stdout("Sending message to: $chatId\n", Console::FG_YELLOW);
        if ($this->verbose) {
            $this->stdout("Message: $message\n");
            $this->stdout("Session: $sessionId\n");
        }
        
        try {
            $response = $whatsapp->sendTextMessage($chatId, $message, [], $sessionId);
            
            if ($response->isSuccessful()) {
                $this->stdout("✓ Message sent successfully!\n", Console::FG_GREEN);
                if ($this->verbose) {
                    $this->stdout("Message ID: " . $response->get('id') . "\n");
                }
            } else {
                $this->stderr("✗ Failed to send message: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Get contacts
     * 
     * @param string|null $sessionId Session ID (optional)
     */
    public function actionContactList($sessionId = null)
    {
        $sessionId = $sessionId ?: $this->sessionId;
        
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->getContacts($sessionId);
            
            if ($response->isSuccessful()) {
                $contacts = $response->getResult();
                
                $this->stdout("Contacts (showing first 10):\n", Console::FG_CYAN);
                
                foreach (array_slice($contacts, 0, 10) as $contact) {
                    $name = $contact['name'] ?? $contact['number'] ?? 'Unknown';
                    $number = $contact['id']['user'] ?? '';
                    $this->stdout("• $name ($number)\n");
                }
                
                if (count($contacts) > 10) {
                    $this->stdout("... and " . (count($contacts) - 10) . " more contacts\n");
                }
                
            } else {
                $this->stderr("✗ Failed to get contacts: " . $response->getErrorMessage() . "\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Health check - ping the API
     */
    public function actionPing()
    {
        try {
            $whatsapp = $this->getWhatsAppClient();
            $response = $whatsapp->ping();
            
            if ($response->isSuccessful()) {
                $this->stdout("✓ API is responsive: " . $response->get('message') . "\n", Console::FG_GREEN);
            } else {
                $this->stderr("✗ API is not responding!\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (WhatsAppException $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Show help information
     */
    public function actionIndex()
    {
        $this->stdout("WhatsApp Web REST Client Console Commands\n", Console::FG_CYAN);
        $this->stdout("==========================================\n\n");
        
        $this->stdout("Session Management:\n", Console::FG_YELLOW);
        $this->stdout("  session/start [sessionId]    Start a new session\n");
        $this->stdout("  session/status [sessionId]   Get session status\n");
        $this->stdout("  session/qr [sessionId]       Get QR code for authentication\n");
        $this->stdout("  session/stop [sessionId]     Stop a session\n");
        $this->stdout("  session/list                 List all sessions\n\n");
        
        $this->stdout("Messaging:\n", Console::FG_YELLOW);
        $this->stdout("  message/send <chatId> <message> [sessionId]  Send a text message\n\n");
        
        $this->stdout("Information:\n", Console::FG_YELLOW);
        $this->stdout("  contact/list [sessionId]     List contacts\n");
        $this->stdout("  ping                         Check API health\n\n");
        
        $this->stdout("Options:\n", Console::FG_YELLOW);
        $this->stdout("  --sessionId, -s    Default session ID (default: 'default')\n");
        $this->stdout("  --verbose, -v      Enable verbose output\n\n");
        
        $this->stdout("Examples:\n", Console::FG_GREEN);
        $this->stdout("  php yii whatsapp/session/start my-session\n");
        $this->stdout("  php yii whatsapp/message/send 1234567890 \"Hello World\" my-session\n");
        $this->stdout("  php yii whatsapp/session/status -s my-session\n");
        
        return self::EXIT_CODE_NORMAL;
    }
}