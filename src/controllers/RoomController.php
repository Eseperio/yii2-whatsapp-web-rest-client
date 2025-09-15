<?php

namespace eseperio\whatsapp\controllers;

use yii\web\Controller;
use yii\data\ArrayDataProvider;
use yii\data\Pagination;
use yii\helpers\Json;
use yii\web\Response;
use eseperio\whatsapp\models\Room;
use eseperio\whatsapp\exceptions\WhatsAppException;

/**
 * Room Controller
 * 
 * Handles WhatsApp room/chat listing and filtering operations
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\controllers
 */
class RoomController extends Controller
{
    /**
     * @var string Default session ID to use for requests
     */
    public $defaultSessionId = 'default';

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Set response format for API actions unless explicitly requesting HTML
        $format = \Yii::$app->request->get('format', 'json');
        if (in_array($action->id, ['index', 'list', 'filter']) && $format !== 'html') {
            \Yii::$app->response->format = Response::FORMAT_JSON;
        }

        return true;
    }

    /**
     * List all rooms with optional filtering
     * 
     * Supported filters:
     * - isGroup: boolean - Filter by group chats
     * - hasNewMessages: boolean - Filter by chats with unread messages
     * - type: string - Chat type (individual, group, broadcast)
     * - isArchived: boolean - Filter by archived status
     * - isPinned: boolean - Filter by pinned status
     * - isMuted: boolean - Filter by muted status
     * - name: string - Filter by chat name (partial match)
     * - minUnreadCount: integer - Minimum unread message count
     * 
     * @param string|null $sessionId Session ID
     * @return array|ArrayDataProvider
     */
    public function actionIndex($sessionId = null)
    {
        try {
            $sessionId = $sessionId ?: $this->defaultSessionId;
            $whatsapp = $this->module->whatsappClient;
            
            // Get filters from request
            $filters = $this->getFiltersFromRequest();
            
            // Get chats from WhatsApp API
            $chatsResponse = $whatsapp->getChats([], $sessionId);
            
            if (!$chatsResponse->isSuccessful()) {
                throw new WhatsAppException('Failed to retrieve chats: ' . $chatsResponse->getErrorMessage());
            }
            
            $chatsData = $chatsResponse->getResult();
            $rooms = [];
            
            // Convert chat data to Room objects and apply filters
            foreach ($chatsData as $chatData) {
                $room = Room::fromApiData($chatData);
                
                if ($room->matchesFilters($filters)) {
                    $rooms[] = $room->toArray();
                }
            }
            
            // Create data provider
            $dataProvider = new ArrayDataProvider([
                'allModels' => $rooms,
                'pagination' => [
                    'pageSize' => \Yii::$app->request->get('per-page', 20),
                ],
                'sort' => [
                    'attributes' => [
                        'name',
                        'timestamp',
                        'unreadCount',
                        'type',
                        'isGroup',
                        'hasNewMessages',
                        'isArchived',
                        'isPinned',
                        'isMuted',
                    ],
                    'defaultOrder' => ['timestamp' => SORT_DESC],
                ],
            ]);
            
            // Return appropriate format based on request
            if (\Yii::$app->request->get('format') === 'raw') {
                return $rooms;
            }
            
            if (\Yii::$app->request->get('format') === 'html') {
                // Return view for web interface
                return $this->render('index', [
                    'dataProvider' => $dataProvider,
                    'filters' => $filters,
                    'sessionId' => $sessionId,
                ]);
            }
            
            return [
                'success' => true,
                'data' => $dataProvider->getModels(),
                'pagination' => [
                    'totalCount' => $dataProvider->getTotalCount(),
                    'pageCount' => $dataProvider->getPagination()->getPageCount(),
                    'currentPage' => $dataProvider->getPagination()->getPage() + 1,
                    'perPage' => $dataProvider->getPagination()->getPageSize(),
                ],
                'filters' => $filters,
                'sessionId' => $sessionId,
            ];
            
        } catch (WhatsAppException $e) {
            \Yii::error('WhatsApp API Error in RoomController::actionIndex: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'whatsapp_api_error',
            ];
            
        } catch (\Exception $e) {
            \Yii::error('General Error in RoomController::actionIndex: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'An unexpected error occurred',
                'code' => 'general_error',
            ];
        }
    }

    /**
     * Get all rooms without filtering (alias for index with no filters)
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionList($sessionId = null)
    {
        // Clear any existing filters
        $request = \Yii::$app->request;
        $params = $request->queryParams;
        
        // Remove all filter parameters
        $filterKeys = [
            'isGroup', 'hasNewMessages', 'type', 'isArchived', 
            'isPinned', 'isMuted', 'name', 'minUnreadCount'
        ];
        
        foreach ($filterKeys as $key) {
            unset($params[$key]);
        }
        
        // Set cleaned parameters back
        $request->setQueryParams($params);
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get groups only
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionGroups($sessionId = null)
    {
        \Yii::$app->request->setQueryParams(
            array_merge(\Yii::$app->request->queryParams, ['isGroup' => 1])
        );
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get individual chats only
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionIndividual($sessionId = null)
    {
        \Yii::$app->request->setQueryParams(
            array_merge(\Yii::$app->request->queryParams, ['isGroup' => 0])
        );
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get chats with new messages
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionUnread($sessionId = null)
    {
        \Yii::$app->request->setQueryParams(
            array_merge(\Yii::$app->request->queryParams, ['hasNewMessages' => 1])
        );
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get archived chats
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionArchived($sessionId = null)
    {
        \Yii::$app->request->setQueryParams(
            array_merge(\Yii::$app->request->queryParams, ['isArchived' => 1])
        );
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get pinned chats
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionPinned($sessionId = null)
    {
        \Yii::$app->request->setQueryParams(
            array_merge(\Yii::$app->request->queryParams, ['isPinned' => 1])
        );
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Custom filter action with POST support for complex filters
     * 
     * @param string|null $sessionId Session ID
     * @return array
     */
    public function actionFilter($sessionId = null)
    {
        if (\Yii::$app->request->isPost) {
            // Handle POST filters
            $postFilters = \Yii::$app->request->post('filters', []);
            
            // Merge with query parameters
            $queryParams = \Yii::$app->request->queryParams;
            foreach ($postFilters as $key => $value) {
                $queryParams[$key] = $value;
            }
            
            \Yii::$app->request->setQueryParams($queryParams);
        }
        
        return $this->actionIndex($sessionId);
    }

    /**
     * Get filter options and metadata
     * 
     * @return array
     */
    public function actionFilterOptions()
    {
        return [
            'success' => true,
            'data' => [
                'availableFilters' => [
                    'isGroup' => [
                        'type' => 'boolean',
                        'description' => 'Filter by group chats (true) or individual chats (false)',
                        'example' => true,
                    ],
                    'hasNewMessages' => [
                        'type' => 'boolean',
                        'description' => 'Filter by chats with unread messages',
                        'example' => true,
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Chat type',
                        'options' => ['individual', 'group', 'broadcast'],
                        'example' => 'group',
                    ],
                    'isArchived' => [
                        'type' => 'boolean',
                        'description' => 'Filter by archived status',
                        'example' => false,
                    ],
                    'isPinned' => [
                        'type' => 'boolean',
                        'description' => 'Filter by pinned status',
                        'example' => true,
                    ],
                    'isMuted' => [
                        'type' => 'boolean',
                        'description' => 'Filter by muted status',
                        'example' => false,
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Filter by chat name (partial match, case-insensitive)',
                        'example' => 'Family',
                    ],
                    'minUnreadCount' => [
                        'type' => 'integer',
                        'description' => 'Minimum unread message count',
                        'example' => 5,
                    ],
                ],
                'supportedSortFields' => [
                    'name' => 'Chat name',
                    'timestamp' => 'Last message time',
                    'unreadCount' => 'Unread message count',
                    'type' => 'Chat type',
                    'isGroup' => 'Group status',
                    'hasNewMessages' => 'Has new messages',
                    'isArchived' => 'Archived status',
                    'isPinned' => 'Pinned status',
                    'isMuted' => 'Muted status',
                ],
                'paginationOptions' => [
                    'per-page' => 'Number of items per page (default: 20)',
                    'page' => 'Page number (1-based)',
                ],
                'examples' => [
                    'Get all groups with unread messages' => '?isGroup=1&hasNewMessages=1',
                    'Get individual chats only' => '?isGroup=0',
                    'Search for chats by name' => '?name=family',
                    'Get chats with 5+ unread messages' => '?minUnreadCount=5',
                    'Get archived groups' => '?isGroup=1&isArchived=1',
                ],
            ],
        ];
    }

    /**
     * Extract and validate filters from request parameters
     * 
     * @return array Validated filters
     */
    protected function getFiltersFromRequest()
    {
        $request = \Yii::$app->request;
        $filters = [];
        
        // Boolean filters
        $booleanFilters = ['isGroup', 'hasNewMessages', 'isArchived', 'isPinned', 'isMuted'];
        foreach ($booleanFilters as $filter) {
            $value = $request->get($filter);
            if ($value !== null) {
                $filters[$filter] = (bool)$value;
            }
        }
        
        // String filters
        $stringFilters = ['type', 'name'];
        foreach ($stringFilters as $filter) {
            $value = $request->get($filter);
            if ($value !== null && $value !== '') {
                $filters[$filter] = (string)$value;
            }
        }
        
        // Integer filters
        $integerFilters = ['minUnreadCount'];
        foreach ($integerFilters as $filter) {
            $value = $request->get($filter);
            if ($value !== null && is_numeric($value)) {
                $filters[$filter] = (int)$value;
            }
        }
        
        return $filters;
    }
}