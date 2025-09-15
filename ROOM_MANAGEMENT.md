# Room Management and Caching Features

This document describes the new room (chat) management features and caching capabilities added to the Yii2 WhatsApp Web REST Client.

## Features Added

### 1. Room Controller with Filtering

The `RoomController` provides comprehensive room/chat listing and filtering capabilities using Yii2's `ArrayDataProvider`.

#### Available Actions

- `index` - List all rooms with optional filtering
- `list` - Get all rooms without filtering (alias for index)
- `groups` - Get group chats only
- `individual` - Get individual chats only
- `unread` - Get chats with new messages
- `archived` - Get archived chats
- `pinned` - Get pinned chats
- `filter` - Custom filtering with POST support
- `filter-options` - Get available filter options and documentation

#### Supported Filters

- `isGroup` (boolean) - Filter by group chats
- `hasNewMessages` (boolean) - Filter by chats with unread messages
- `type` (string) - Chat type (individual, group, broadcast)
- `isArchived` (boolean) - Filter by archived status
- `isPinned` (boolean) - Filter by pinned status
- `isMuted` (boolean) - Filter by muted status
- `name` (string) - Filter by chat name (partial match)
- `minUnreadCount` (integer) - Minimum unread message count

### 2. Caching Integration

Added configurable caching support to improve performance for frequently accessed data.

#### Cache Configuration Properties

- `enableCache` (boolean) - Enable/disable caching for API requests
- `cacheComponent` (string) - Name of the cache component to use (default: 'cache')
- `cacheDuration` (integer) - Cache duration in seconds (default: 300)

## Configuration

### Enable Room Management and Caching

```php
'modules' => [
    'whatsapp' => [
        'class' => 'eseperio\whatsapp\WhatsAppModule',
        
        // Enable caching
        'enableCache' => true,
        'cacheComponent' => 'cache', // or 'redis', 'memcache', etc.
        'cacheDuration' => 300, // 5 minutes
        
        // Other existing configuration...
        'whatsappClientConfig' => [
            'baseUrl' => 'http://localhost:3000',
            'apiKey' => 'your-api-key',
            'defaultSessionId' => 'default',
            'timeout' => 30,
        ],
    ],
],
```

### Add Routes (Optional)

Add routes to your URL manager for web access:

```php
'urlManager' => [
    'rules' => [
        'whatsapp/rooms' => 'whatsapp/room/index',
        'whatsapp/rooms/<action>' => 'whatsapp/room/<action>',
        'whatsapp/rooms/<action>/<sessionId>' => 'whatsapp/room/<action>',
    ],
],
```

## Usage Examples

### API Usage (JSON responses)

```php
// Get all rooms
$url = '/whatsapp/rooms?sessionId=demo';

// Filter groups with new messages
$url = '/whatsapp/rooms?isGroup=1&hasNewMessages=1&sessionId=demo';

// Search rooms by name
$url = '/whatsapp/rooms?name=family&sessionId=demo';

// Get rooms with 5+ unread messages
$url = '/whatsapp/rooms?minUnreadCount=5&sessionId=demo';

// Get archived groups
$url = '/whatsapp/rooms?isGroup=1&isArchived=1&sessionId=demo';
```

### Programmatic Usage

```php
use eseperio\whatsapp\controllers\RoomController;

$module = Yii::$app->getModule('whatsapp');
$controller = new RoomController('room', $module);

// Get all rooms
$result = $controller->actionList('demo-session');

// Filter groups with new messages
Yii::$app->request->setQueryParams([
    'isGroup' => 1,
    'hasNewMessages' => 1
]);
$result = $controller->actionIndex('demo-session');

// Use custom filters via POST
$_POST['filters'] = [
    'isGroup' => true,
    'minUnreadCount' => 2,
    'isMuted' => false
];
$result = $controller->actionFilter('demo-session');
```

### Web Interface Usage

Access the web interface by adding `?format=html` to any URL:

```
/whatsapp/rooms?format=html
/whatsapp/rooms/groups?format=html
```

## Response Format

### JSON API Response

```json
{
    "success": true,
    "data": [
        {
            "id": "1234567890@c.us",
            "name": "John Doe",
            "isGroup": false,
            "unreadCount": 3,
            "hasNewMessages": true,
            "lastMessage": {
                "body": "Hello there!",
                "timestamp": 1640995200
            },
            "timestamp": 1640995200,
            "type": "individual",
            "isArchived": false,
            "isPinned": true,
            "isMuted": false,
            "metadata": {}
        }
    ],
    "pagination": {
        "totalCount": 25,
        "pageCount": 3,
        "currentPage": 1,
        "perPage": 20
    },
    "filters": {
        "isGroup": false,
        "hasNewMessages": true
    },
    "sessionId": "demo-session"
}
```

### Filter Options Response

```json
{
    "success": true,
    "data": {
        "availableFilters": {
            "isGroup": {
                "type": "boolean",
                "description": "Filter by group chats (true) or individual chats (false)",
                "example": true
            },
            "hasNewMessages": {
                "type": "boolean", 
                "description": "Filter by chats with unread messages",
                "example": true
            }
        },
        "examples": {
            "Get all groups with unread messages": "?isGroup=1&hasNewMessages=1",
            "Get individual chats only": "?isGroup=0"
        }
    }
}
```

## Caching Behavior

### Cached Operations

The following API operations are automatically cached when caching is enabled:

- `getContacts()` - Contact list
- `getChats()` - Chat list (used by room controller)
- `getSessionStatus()` - Session status
- `getClientState()` - Client state
- `getClientInfo()` - Client information
- `getWWebVersion()` - WhatsApp Web version

### Cache Key Generation

Cache keys are automatically generated based on:
- HTTP method
- API endpoint
- Session ID
- Request parameters

### Cache Invalidation

- Cache is automatically invalidated after the configured duration
- Only successful API responses are cached
- GET requests are cached; POST/PUT/DELETE requests are not cached

## Error Handling

```php
try {
    $result = $controller->actionIndex('demo-session');
    
    if (!$result['success']) {
        // Handle API errors
        echo "Error: " . $result['error'];
        echo "Code: " . $result['code'];
    }
    
} catch (WhatsAppException $e) {
    // Handle WhatsApp-specific errors
    echo "WhatsApp Error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle general errors
    echo "General Error: " . $e->getMessage();
}
```

## Performance Considerations

- Enable caching for production environments
- Adjust `cacheDuration` based on your needs (shorter for real-time apps, longer for reporting)
- Use appropriate cache backends (Redis, Memcache) for high-traffic applications
- Monitor cache hit rates and adjust configuration accordingly

## Backwards Compatibility

- All existing functionality remains unchanged
- New features are opt-in through configuration
- Caching is disabled by default to maintain existing behavior
- The Room controller is a new addition and doesn't affect existing code