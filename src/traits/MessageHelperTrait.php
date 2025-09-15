<?php

namespace eseperio\whatsapp\traits;

use eseperio\whatsapp\models\ApiResponse;

/**
 * Message Helper Trait
 * 
 * Provides convenient methods for common message operations
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\traits
 */
trait MessageHelperTrait
{
    /**
     * Send a simple text message with optional formatting
     * 
     * @param string $chatId Chat ID
     * @param string $text Message text
     * @param bool $bold Make text bold
     * @param bool $italic Make text italic
     * @param bool $monospace Make text monospace
     * @param array $options Additional options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendFormattedText($chatId, $text, $bold = false, $italic = false, $monospace = false, $options = [], $sessionId = null)
    {
        if ($bold) {
            $text = "*{$text}*";
        }
        if ($italic) {
            $text = "_{$text}_";
        }
        if ($monospace) {
            $text = "```{$text}```";
        }
        
        return $this->sendTextMessage($chatId, $text, $options, $sessionId);
    }

    /**
     * Send a message with mentions
     * 
     * @param string $chatId Chat ID
     * @param string $text Message text (use @username for mentions)
     * @param array $mentions Array of contact IDs to mention
     * @param array $options Additional options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendTextWithMentions($chatId, $text, $mentions = [], $options = [], $sessionId = null)
    {
        $options['mentions'] = $mentions;
        return $this->sendTextMessage($chatId, $text, $options, $sessionId);
    }

    /**
     * Send a quick emoji reaction
     * 
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param string $emoji Emoji (👍, ❤️, 😂, 😮, 😢, 🙏, etc.)
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function quickReact($chatId, $messageId, $emoji, $sessionId = null)
    {
        return $this->reactToMessage($chatId, $messageId, $emoji, $sessionId);
    }

    /**
     * Remove reaction from message
     * 
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function removeReaction($chatId, $messageId, $sessionId = null)
    {
        return $this->reactToMessage($chatId, $messageId, '', $sessionId);
    }

    /**
     * Send a message and immediately mark chat as seen
     * 
     * @param string $chatId Chat ID
     * @param string $contentType Content type
     * @param mixed $content Message content
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendAndMarkSeen($chatId, $contentType, $content, $options = [], $sessionId = null)
    {
        $response = $this->sendMessage($chatId, $contentType, $content, $options, $sessionId);
        
        if ($response->isSuccessful()) {
            $this->markChatAsSeen($chatId, $sessionId);
        }
        
        return $response;
    }

    /**
     * Send typing indicator and then send message
     * 
     * @param string $chatId Chat ID
     * @param string $contentType Content type
     * @param mixed $content Message content
     * @param int $typingDuration Typing duration in seconds
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendWithTyping($chatId, $contentType, $content, $typingDuration = 2, $options = [], $sessionId = null)
    {
        // Send typing indicator
        $this->sendTyping($chatId, $sessionId);
        
        // Wait for specified duration
        sleep($typingDuration);
        
        // Send the actual message
        return $this->sendMessage($chatId, $contentType, $content, $options, $sessionId);
    }

    /**
     * Broadcast message to multiple chats
     * 
     * @param array $chatIds Array of chat IDs
     * @param string $contentType Content type
     * @param mixed $content Message content
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return array Array of ApiResponse objects
     */
    public function broadcastMessage($chatIds, $contentType, $content, $options = [], $sessionId = null)
    {
        $responses = [];
        
        foreach ($chatIds as $chatId) {
            $responses[$chatId] = $this->sendMessage($chatId, $contentType, $content, $options, $sessionId);
            
            // Small delay between messages to avoid being rate limited
            usleep(500000); // 0.5 seconds
        }
        
        return $responses;
    }
}