<?php

namespace eseperio\whatsapp\models;

use yii\base\BaseObject;

/**
 * Room Model
 * 
 * Represents a WhatsApp chat/room with filtering capabilities
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\models
 */
class Room extends BaseObject
{
    /**
     * @var string Chat ID
     */
    public $id;

    /**
     * @var string Chat name/title
     */
    public $name;

    /**
     * @var bool Whether this is a group chat
     */
    public $isGroup;

    /**
     * @var int Number of unread messages
     */
    public $unreadCount = 0;

    /**
     * @var bool Whether this chat has new messages
     */
    public $hasNewMessages = false;

    /**
     * @var array Last message data
     */
    public $lastMessage;

    /**
     * @var int Timestamp of last message
     */
    public $timestamp;

    /**
     * @var string Chat type (individual, group, broadcast, etc.)
     */
    public $type;

    /**
     * @var bool Whether the chat is archived
     */
    public $isArchived = false;

    /**
     * @var bool Whether the chat is pinned
     */
    public $isPinned = false;

    /**
     * @var bool Whether the chat is muted
     */
    public $isMuted = false;

    /**
     * @var array Additional chat metadata
     */
    public $metadata = [];

    /**
     * Create Room instance from WhatsApp chat data
     * 
     * @param array $chatData Raw chat data from WhatsApp API
     * @return Room
     */
    public static function fromApiData($chatData)
    {
        $room = new self();
        
        // Basic properties
        $room->id = $chatData['id']['_serialized'] ?? $chatData['id'] ?? null;
        $room->name = $chatData['name'] ?? 'Unknown';
        $room->isGroup = $chatData['isGroup'] ?? false;
        $room->unreadCount = $chatData['unreadCount'] ?? 0;
        $room->hasNewMessages = $room->unreadCount > 0;
        
        // Last message
        $room->lastMessage = $chatData['lastMessage'] ?? null;
        if ($room->lastMessage) {
            $room->timestamp = $room->lastMessage['timestamp'] ?? time();
        } else {
            $room->timestamp = time();
        }
        
        // Chat type
        $room->type = 'individual';
        if ($room->isGroup) {
            $room->type = 'group';
        }
        
        // Additional properties
        $room->isArchived = $chatData['archived'] ?? false;
        $room->isPinned = $chatData['pinned'] ?? false;
        $room->isMuted = $chatData['isMuted'] ?? false;
        
        // Store additional metadata
        $room->metadata = array_diff_key($chatData, array_flip([
            'id', 'name', 'isGroup', 'unreadCount', 'lastMessage', 'archived', 'pinned', 'isMuted'
        ]));
        
        return $room;
    }

    /**
     * Convert room to array representation
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isGroup' => $this->isGroup,
            'unreadCount' => $this->unreadCount,
            'hasNewMessages' => $this->hasNewMessages,
            'lastMessage' => $this->lastMessage,
            'timestamp' => $this->timestamp,
            'type' => $this->type,
            'isArchived' => $this->isArchived,
            'isPinned' => $this->isPinned,
            'isMuted' => $this->isMuted,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get formatted last message time
     * 
     * @return string
     */
    public function getFormattedTime()
    {
        if (!$this->timestamp) {
            return '';
        }
        
        return date('Y-m-d H:i:s', $this->timestamp);
    }

    /**
     * Get last message body (truncated)
     * 
     * @param int $length Maximum length
     * @return string
     */
    public function getLastMessageBody($length = 50)
    {
        if (!$this->lastMessage || !isset($this->lastMessage['body'])) {
            return 'No messages';
        }
        
        $body = $this->lastMessage['body'];
        if (strlen($body) > $length) {
            return substr($body, 0, $length) . '...';
        }
        
        return $body;
    }

    /**
     * Check if room matches filter criteria
     * 
     * @param array $filters Filter criteria
     * @return bool
     */
    public function matchesFilters($filters)
    {
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'isGroup':
                    if ((bool)$value !== $this->isGroup) {
                        return false;
                    }
                    break;
                
                case 'hasNewMessages':
                    if ((bool)$value !== $this->hasNewMessages) {
                        return false;
                    }
                    break;
                
                case 'type':
                    if ($value !== $this->type) {
                        return false;
                    }
                    break;
                
                case 'isArchived':
                    if ((bool)$value !== $this->isArchived) {
                        return false;
                    }
                    break;
                
                case 'isPinned':
                    if ((bool)$value !== $this->isPinned) {
                        return false;
                    }
                    break;
                
                case 'isMuted':
                    if ((bool)$value !== $this->isMuted) {
                        return false;
                    }
                    break;
                
                case 'name':
                    if (!empty($value) && stripos($this->name, $value) === false) {
                        return false;
                    }
                    break;
                
                case 'minUnreadCount':
                    if ($this->unreadCount < (int)$value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
}