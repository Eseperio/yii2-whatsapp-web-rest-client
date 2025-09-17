<?php

namespace eseperio\whatsapp\components;

use eseperio\whatsapp\exceptions\WhatsAppException;
use eseperio\whatsapp\models\ApiResponse;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\httpclient\Client;

/**
 * WhatsApp Client Component
 *
 * Main component for interacting with the WhatsApp Web REST API
 * through the avoylenko/wwebjs-api docker container.
 *
 * @author E.Alamo
 * @package eseperio\whatsapp\components
 */
class WhatsAppClient extends Component
{
    /**
     * @var string Base URL of the WhatsApp Web REST API
     */
    public $baseUrl = 'http://localhost:3000';

    /**
     * @var string|null API key for authentication (if required)
     */
    public $apiKey;

    /**
     * @var string Default session ID to use when none specified
     */
    public $defaultSessionId = 'default';

    /**
     * @var int Request timeout in seconds
     */
    public $timeout = 30;

    /**
     * @var bool Enable/disable caching for API requests
     */
    public $enableCache = false;

    /**
     * @var string Name of the cache component to use
     */
    public $cacheComponent = 'cache';

    /**
     * @var int Cache duration in seconds (default: 5 minutes)
     */
    public $cacheDuration = 300;

    /**
     * @var Client HTTP client instance
     */
    private $_httpClient;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->baseUrl)) {
            throw new InvalidConfigException('The "baseUrl" property must be set.');
        }
        if (empty($this->apiKey)) {
            throw new InvalidConfigException('The "apiKey" property must be set.');
        }

        // Inicializar sin baseUrl ni timeout (se aplican por request)
        $this->_httpClient = new Client();
    }

    /**
     * Get the HTTP client instance
     *
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }

    /**
     * Get cache component instance
     *
     * @return CacheInterface|null
     */
    protected function getCache()
    {
        if (!$this->enableCache) {
            return null;
        }

        try {
            return Yii::$app->get($this->cacheComponent);
        } catch (\Exception $e) {
            Yii::warning("Cache component '{$this->cacheComponent}' not found: " . $e->getMessage(), __CLASS__);
            return null;
        }
    }

    /**
     * Generate cache key for request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string|null $sessionId Session ID
     * @return string
     */
    protected function generateCacheKey($method, $endpoint, $data = [], $sessionId = null)
    {
        $key = sprintf(
            'whatsapp_api_%s_%s_%s_%s',
            $method,
            str_replace(['/', '{', '}'], ['_', '', ''], $endpoint),
            $sessionId ?: $this->defaultSessionId,
            md5(serialize($data))
        );

        return $key;
    }

    /**
     * Check if request should be cached
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @return bool
     */
    protected function shouldCache($method, $endpoint)
    {
        // Only cache GET requests for specific endpoints
        if (strtoupper($method) !== 'GET') {
            return false;
        }

        $cacheableEndpoints = [
            '/client/getContacts',
            '/client/getChats',
            '/session/status',
            '/client/getState',
            '/client/getClassInfo',
            '/client/getWWebVersion'
        ];

        foreach ($cacheableEndpoints as $cacheableEndpoint) {
            if (strpos($endpoint, $cacheableEndpoint) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Make an API request
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string|null $sessionId Session ID (uses default if null)
     * @return ApiResponse
     * @throws WhatsAppException
     */
    protected function makeRequest($method, $endpoint, $data = [], $sessionId = null)
    {
        if ($sessionId === null) {
            $sessionId = $this->defaultSessionId;
        }

        // Replace {sessionId} placeholder in endpoint
        $endpoint = str_replace('{sessionId}', $sessionId, $endpoint);

        // Check cache first
        $cache = $this->getCache();
        $cacheKey = null;

        if ($cache && $this->shouldCache($method, $endpoint)) {
            $cacheKey = $this->generateCacheKey($method, $endpoint, $data, $sessionId);
            $cachedResponse = $cache->get($cacheKey);

            if ($cachedResponse !== false) {
                return $cachedResponse;
            }
        }

        try {
            $request = $this->_httpClient->createRequest()
                ->setFormat(Client::FORMAT_JSON)
                ->setMethod($method)
                ->setUrl($this->baseUrl . $endpoint)
                ->setOptions(['timeout' => $this->timeout]);

            // Add API key header if configured
            if ($this->apiKey !== null) {
                $request->addHeaders(['x-api-key' => $this->apiKey]);
            }

            // Add data based on method
            if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                $request->setData($data);
            } elseif (!empty($data)) {
                $request->setData($data);
            }

            $response = $request->send();
            $response->setFormat(Client::FORMAT_JSON);
            $apiResponse = new ApiResponse([
                'success' => $response->isOk,
                'statusCode' => $response->statusCode,
                'data' => $response->data,
                'rawResponse' => $response,
            ]);

            if ($cache && $cacheKey) {
                $cache->set($cacheKey, $apiResponse, $this->cacheDuration);
            }

            return $apiResponse;

        } catch (\Throwable $e) {
            if (isset($cache) && $cache && isset($cacheKey) && $cacheKey) {
                $cache->delete($cacheKey);
            }
            throw new WhatsAppException('API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Health check - ping the API
     *
     * @return ApiResponse
     */
    public function ping()
    {
        return $this->makeRequest('GET', '/ping');
    }

    // Session Management Methods

    /**
     * Start a new session
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function startSession($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/start/{sessionId}', [], $sessionId);
    }

    /**
     * Stop a session
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function stopSession($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/stop/{sessionId}', [], $sessionId);
    }

    /**
     * Get session status
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getSessionStatus($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/status/{sessionId}', [], $sessionId);
    }

    /**
     * Restart a session
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function restartSession($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/restart/{sessionId}', [], $sessionId);
    }

    /**
     * Terminate a session
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function terminateSession($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/terminate/{sessionId}', [], $sessionId);
    }

    /**
     * Get QR code for session authentication
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getSessionQr($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/qr/{sessionId}', [], $sessionId);
    }

    /**
     * Get QR code as image
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getSessionQrImage($sessionId = null)
    {
        return $this->makeRequest('GET', '/session/qr/{sessionId}/image', [], $sessionId);
    }

    /**
     * Get all sessions
     *
     * @return ApiResponse
     */
    public function getSessions()
    {
        return $this->makeRequest('GET', '/session/getSessions');
    }

    // Client Information Methods

    /**
     * Get client state
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getClientState($sessionId = null)
    {
        return $this->makeRequest('GET', '/client/getState/{sessionId}', [], $sessionId);
    }

    /**
     * Get client class information
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getClientInfo($sessionId = null)
    {
        return $this->makeRequest('GET', '/client/getClassInfo/{sessionId}', [], $sessionId);
    }

    /**
     * Get WhatsApp Web version
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getWWebVersion($sessionId = null)
    {
        return $this->makeRequest('GET', '/client/getWWebVersion/{sessionId}', [], $sessionId);
    }

    // Contact Methods

    /**
     * Get all contacts
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getContacts($sessionId = null)
    {
        return $this->makeRequest('GET', '/client/getContacts/{sessionId}', [], $sessionId);
    }

    /**
     * Get contact by ID
     *
     * @param string $contactId Contact ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getContactById($contactId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/getContactById/{sessionId}', [
            'contactId' => $contactId
        ], $sessionId);
    }

    /**
     * Check if number is registered on WhatsApp
     *
     * @param string $number Phone number
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function isRegisteredUser($number, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/isRegisteredUser/{sessionId}', [
            'number' => $number
        ], $sessionId);
    }

    /**
     * Get profile picture URL for a contact
     *
     * @param string $contactId Contact ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getProfilePicUrl($contactId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/getProfilePicUrl/{sessionId}', [
            'contactId' => $contactId
        ], $sessionId);
    }

    // Chat Methods

    /**
     * Get all chats
     *
     * @param array $searchOptions Optional search options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getChats($searchOptions = [], $sessionId = null)
    {
        if (empty($searchOptions)) {
            return $this->makeRequest('GET', '/client/getChats/{sessionId}', [], $sessionId);
        } else {
            return $this->makeRequest('POST', '/client/getChats/{sessionId}', [
                'searchOptions' => $searchOptions
            ], $sessionId);
        }
    }

    /**
     * Get chat by ID
     *
     * @param string $chatId Chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getChatById($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/getChatById/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    // Message Methods

    /**
     * Send a message
     *
     * @param string $chatId Chat ID
     * @param string $contentType Content type (string, MessageMedia, etc.)
     * @param mixed $content Message content
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendMessage($chatId, $contentType, $content, $options = [], $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/sendMessage/{sessionId}', [
            'chatId' => $chatId,
            'contentType' => $contentType,
            'content' => $content,
            'options' => $options
        ], $sessionId);
    }

    /**
     * Send a text message
     *
     * @param string $chatId Chat ID
     * @param string $text Message text
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendTextMessage($chatId, $text, $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'string', $text, $options, $sessionId);
    }

    /**
     * Send media message
     *
     * @param string $chatId Chat ID
     * @param array $mediaData Media data with mimetype, data, and optional filename
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendMediaMessage($chatId, $mediaData, $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'MessageMedia', $mediaData, $options, $sessionId);
    }

    /**
     * Send media from URL
     *
     * @param string $chatId Chat ID
     * @param string $url Media URL
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendMediaFromUrl($chatId, $url, $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'MessageMediaFromURL', $url, $options, $sessionId);
    }

    /**
     * Mark chat as seen
     *
     * @param string $chatId Chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function markChatAsSeen($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/sendSeen/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    // Group Chat Methods

    /**
     * Create a new group
     *
     * @param string $title Group title
     * @param array $participants Array of participant IDs
     * @param array $options Group creation options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function createGroup($title, $participants = [], $options = [], $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/createGroup/{sessionId}', [
            'title' => $title,
            'participants' => $participants,
            'options' => $options
        ], $sessionId);
    }

    /**
     * Get group invite code
     *
     * @param string $chatId Group chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getGroupInviteCode($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/getInviteCode/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    /**
     * Add participants to group
     *
     * @param string $chatId Group chat ID
     * @param array $participantIds Array of participant IDs
     * @param array $options Options for adding participants
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function addGroupParticipants($chatId, $participantIds, $options = [], $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/addParticipants/{sessionId}', [
            'chatId' => $chatId,
            'participantIds' => $participantIds,
            'options' => $options
        ], $sessionId);
    }

    /**
     * Remove participants from group
     *
     * @param string $chatId Group chat ID
     * @param array $participantIds Array of participant IDs
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function removeGroupParticipants($chatId, $participantIds, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/removeParticipants/{sessionId}', [
            'chatId' => $chatId,
            'participantIds' => $participantIds
        ], $sessionId);
    }

    /**
     * Promote participants to group admins
     *
     * @param string $chatId Group chat ID
     * @param array $participantIds Array of participant IDs
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function promoteGroupParticipants($chatId, $participantIds, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/promoteParticipants/{sessionId}', [
            'chatId' => $chatId,
            'participantIds' => $participantIds
        ], $sessionId);
    }

    /**
     * Demote group admins to regular participants
     *
     * @param string $chatId Group chat ID
     * @param array $participantIds Array of participant IDs
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function demoteGroupParticipants($chatId, $participantIds, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/demoteParticipants/{sessionId}', [
            'chatId' => $chatId,
            'participantIds' => $participantIds
        ], $sessionId);
    }

    /**
     * Set group subject/title
     *
     * @param string $chatId Group chat ID
     * @param string $subject New group subject
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function setGroupSubject($chatId, $subject, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/setSubject/{sessionId}', [
            'chatId' => $chatId,
            'subject' => $subject
        ], $sessionId);
    }

    /**
     * Set group description
     *
     * @param string $chatId Group chat ID
     * @param string $description New group description
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function setGroupDescription($chatId, $description, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/setDescription/{sessionId}', [
            'chatId' => $chatId,
            'description' => $description
        ], $sessionId);
    }

    /**
     * Leave a group
     *
     * @param string $chatId Group chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function leaveGroup($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/groupChat/leave/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    // Contact Management Methods

    /**
     * Block a contact
     *
     * @param string $contactId Contact ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function blockContact($contactId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/contact/block/{sessionId}', [
            'contactId' => $contactId
        ], $sessionId);
    }

    /**
     * Unblock a contact
     *
     * @param string $contactId Contact ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function unblockContact($contactId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/contact/unblock/{sessionId}', [
            'contactId' => $contactId
        ], $sessionId);
    }

    /**
     * Get contact's about information
     *
     * @param string $contactId Contact ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getContactAbout($contactId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/contact/getAbout/{sessionId}', [
            'contactId' => $contactId
        ], $sessionId);
    }

    /**
     * Get blocked contacts
     *
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getBlockedContacts($sessionId = null)
    {
        return $this->makeRequest('POST', '/client/getBlockedContacts/{sessionId}', [], $sessionId);
    }

    // Extended Message Methods

    /**
     * Send location message
     *
     * @param string $chatId Chat ID
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $description Location description
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendLocationMessage($chatId, $latitude, $longitude, $description = '', $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'Location', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => $description
        ], $options, $sessionId);
    }

    /**
     * Send contact message
     *
     * @param string $chatId Chat ID
     * @param string $contactId Contact ID to share
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendContactMessage($chatId, $contactId, $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'Contact', [
            'contactId' => $contactId
        ], $options, $sessionId);
    }

    /**
     * Send poll message
     *
     * @param string $chatId Chat ID
     * @param string $pollName Poll question
     * @param array $pollOptions Poll answer options
     * @param array $pollSettings Poll settings
     * @param array $options Send options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendPollMessage($chatId, $pollName, $pollOptions, $pollSettings = [], $options = [], $sessionId = null)
    {
        return $this->sendMessage($chatId, 'Poll', [
            'pollName' => $pollName,
            'pollOptions' => $pollOptions,
            'options' => $pollSettings
        ], $options, $sessionId);
    }

    /**
     * Reply to a message
     *
     * @param string $chatId Chat ID
     * @param string $messageId Message ID to reply to
     * @param string $contentType Content type
     * @param mixed $content Reply content
     * @param array $options Reply options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function replyToMessage($chatId, $messageId, $contentType, $content, $options = [], $sessionId = null)
    {
        return $this->makeRequest('POST', '/message/reply/{sessionId}', [
            'chatId' => $chatId,
            'messageId' => $messageId,
            'contentType' => $contentType,
            'content' => $content,
            'options' => $options
        ], $sessionId);
    }

    /**
     * React to a message
     *
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param string $reaction Emoji reaction (empty string to remove)
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function reactToMessage($chatId, $messageId, $reaction, $sessionId = null)
    {
        return $this->makeRequest('POST', '/message/react/{sessionId}', [
            'chatId' => $chatId,
            'messageId' => $messageId,
            'reaction' => $reaction
        ], $sessionId);
    }

    /**
     * Delete a message
     *
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param bool $forEveryone Delete for everyone
     * @param bool $clearMedia Clear associated media
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function deleteMessage($chatId, $messageId, $forEveryone = false, $clearMedia = false, $sessionId = null)
    {
        return $this->makeRequest('POST', '/message/delete/{sessionId}', [
            'chatId' => $chatId,
            'messageId' => $messageId,
            'everyone' => $forEveryone,
            'clearMedia' => $clearMedia
        ], $sessionId);
    }

    /**
     * Download message media
     *
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function downloadMessageMedia($chatId, $messageId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/message/downloadMedia/{sessionId}', [
            'chatId' => $chatId,
            'messageId' => $messageId
        ], $sessionId);
    }

    /**
     * Get message information
     *
     * @param string $chatId Chat ID
     * @param string $messageId Message ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function getMessageInfo($chatId, $messageId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/message/getInfo/{sessionId}', [
            'chatId' => $chatId,
            'messageId' => $messageId
        ], $sessionId);
    }

    // Chat Presence Methods

    /**
     * Send typing indicator
     *
     * @param string $chatId Chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendTyping($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/chat/sendStateTyping/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    /**
     * Send recording indicator
     *
     * @param string $chatId Chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function sendRecording($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/chat/sendStateRecording/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    /**
     * Stop typing/recording indicators
     *
     * @param string $chatId Chat ID
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function clearChatState($chatId, $sessionId = null)
    {
        return $this->makeRequest('POST', '/chat/clearState/{sessionId}', [
            'chatId' => $chatId
        ], $sessionId);
    }

    // Utility Methods

    /**
     * Set user status message
     *
     * @param string $status Status message
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function setStatus($status, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/setStatus/{sessionId}', [
            'status' => $status
        ], $sessionId);
    }

    /**
     * Set profile picture
     *
     * @param string $mimeType Image MIME type
     * @param string $data Base64 encoded image data
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function setProfilePicture($mimeType, $data, $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/setProfilePicture/{sessionId}', [
            'pictureMimetype' => $mimeType,
            'pictureData' => $data
        ], $sessionId);
    }

    /**
     * Search messages
     *
     * @param string $query Search query
     * @param array $options Search options
     * @param string|null $sessionId Session ID
     * @return ApiResponse
     */
    public function searchMessages($query, $options = [], $sessionId = null)
    {
        return $this->makeRequest('POST', '/client/searchMessages/{sessionId}', [
            'query' => $query,
            'options' => $options
        ], $sessionId);
    }
}
