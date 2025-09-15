# Yii2 WhatsApp Web REST Client

A Yii2 client library to handle connections with WhatsApp Web REST API through the [avoylenko/wwebjs-api](https://github.com/avoylenko/wwebjs-api) Docker container.

This library provides a comprehensive Yii2 module and component for interacting with WhatsApp Web through a REST API wrapper for the whatsapp-web.js library.

## Features

- **Session Management**: Start, stop, restart, and monitor WhatsApp Web sessions
- **Messaging**: Send text, media, location, contact, and poll messages
- **Group Management**: Create groups, manage participants, modify settings
- **Contact Management**: Block/unblock contacts, get contact information
- **Message Operations**: Reply, react, delete, and download message media
- **Chat Features**: Typing indicators, read receipts, chat management
- **Room Management**: List and filter chats/rooms with advanced filtering options
- **Media Support**: Send images, videos, audio, documents from URLs or base64 data
- **Caching Support**: Configurable caching for improved performance
- **Configurable**: Enable/disable specific features through module configuration
- **Error Handling**: Comprehensive exception handling and response models

## Requirements

- PHP >= 7.4
- Yii2 >= 2.0.14
- [avoylenko/wwebjs-api](https://github.com/avoylenko/wwebjs-api) Docker container running

## Installation

Install via Composer:

```bash
composer require eseperio/yii2-whatsapp-web-rest-client
```

## Setup

### 1. Configure the Module

Add the module to your Yii2 application config:

```php
'modules' => [
    'whatsapp' => [
        'class' => 'eseperio\whatsapp\WhatsAppModule',
        // Enable/disable features
        'enableSessionManagement' => true,
        'enableMessaging' => true,
        'enableContactManagement' => true,
        'enableGroupChat' => true,
        'enableChannels' => true,
        'enableMedia' => true,
        // Component configuration
        'whatsappClientConfig' => [
            'baseUrl' => 'http://localhost:3000', // Your wwebjs-api URL
            'apiKey' => 'your-api-key', // Optional API key
            'defaultSessionId' => 'default',
            'timeout' => 30,
        ],
        // Caching configuration (optional)
        'enableCache' => true, // Enable caching for better performance
        'cacheComponent' => 'cache', // Cache component name
        'cacheDuration' => 300, // Cache duration in seconds (5 minutes)
    ],
],
```

### 2. Start the wwebjs-api Container

Make sure you have the WhatsApp Web REST API container running:

```bash
docker run -d \
  --name wwebjs-api \
  -p 3000:3000 \
  -v $(pwd)/sessions:/app/sessions \
  avoylenko/wwebjs-api
```

## Usage

### Basic Usage

```php
// Get the WhatsApp client component
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Start a session
$response = $whatsapp->startSession('my-session');
if ($response->isSuccessful()) {
    echo "Session started successfully\n";
}

// Get QR code for authentication
$qrResponse = $whatsapp->getSessionQr('my-session');
echo "Scan this QR code: " . $qrResponse->get('qr') . "\n";

// Check session status
$status = $whatsapp->getSessionStatus('my-session');
echo "Session state: " . $status->get('state') . "\n";
```

### Sending Messages

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Send text message
$response = $whatsapp->sendTextMessage(
    '1234567890@c.us', // Chat ID
    'Hello from Yii2!',
    [], // Options
    'my-session' // Session ID
);

// Send media from URL
$response = $whatsapp->sendMediaFromUrl(
    '1234567890@c.us',
    'https://example.com/image.jpg',
    ['caption' => 'Check out this image!']
);

// Send location
$response = $whatsapp->sendLocationMessage(
    '1234567890@c.us',
    -6.2, // Latitude
    106.8, // Longitude
    'Jakarta, Indonesia'
);

// Send poll
$response = $whatsapp->sendPollMessage(
    '1234567890@c.us',
    'What is your favorite color?',
    ['Red', 'Blue', 'Green'],
    ['allowMultipleAnswers' => false]
);
```

### Group Management

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Create a group
$response = $whatsapp->createGroup(
    'My Group',
    ['1234567890@c.us', '0987654321@c.us'], // Participants
    [] // Options
);

$groupId = $response->get('id');

// Add participants
$whatsapp->addGroupParticipants($groupId, ['1111111111@c.us']);

// Promote to admin
$whatsapp->promoteGroupParticipants($groupId, ['1234567890@c.us']);

// Set group description
$whatsapp->setGroupDescription($groupId, 'This is our group chat');

// Get invite code
$inviteResponse = $whatsapp->getGroupInviteCode($groupId);
echo "Invite link: https://chat.whatsapp.com/" . $inviteResponse->getResult();
```

### Contact Management

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Get all contacts
$contacts = $whatsapp->getContacts();
foreach ($contacts->getResult() as $contact) {
    echo $contact['name'] . ": " . $contact['number'] . "\n";
}

// Check if number is registered on WhatsApp
$isRegistered = $whatsapp->isRegisteredUser('1234567890');
if ($isRegistered->getResult()) {
    echo "Number is registered on WhatsApp\n";
}

// Block a contact
$whatsapp->blockContact('1234567890@c.us');

// Get contact profile picture
$profilePic = $whatsapp->getProfilePicUrl('1234567890@c.us');
echo "Profile picture URL: " . $profilePic->getResult();
```

### Message Operations

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Reply to a message
$response = $whatsapp->replyToMessage(
    '1234567890@c.us',
    'message-id-to-reply-to',
    'string',
    'This is my reply'
);

// React to a message
$whatsapp->reactToMessage(
    '1234567890@c.us',
    'message-id',
    '👍' // Emoji reaction
);

// Delete a message
$whatsapp->deleteMessage(
    '1234567890@c.us',
    'message-id',
    true, // Delete for everyone
    true  // Clear media
);

// Download message media
$media = $whatsapp->downloadMessageMedia('1234567890@c.us', 'message-id');
if ($media->isSuccessful()) {
    $mediaData = $media->getResult();
    file_put_contents('downloaded_media.' . $mediaData['mimetype'], base64_decode($mediaData['data']));
}
```

### Chat Features

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Send typing indicator
$whatsapp->sendTyping('1234567890@c.us');

// Send recording indicator  
$whatsapp->sendRecording('1234567890@c.us');

// Stop indicators
$whatsapp->clearChatState('1234567890@c.us');

// Mark chat as seen
$whatsapp->markChatAsSeen('1234567890@c.us');

// Get all chats
$chats = $whatsapp->getChats();
foreach ($chats->getResult() as $chat) {
    echo $chat['name'] . " - " . $chat['lastMessage']['body'] . "\n";
}
```

### Room Management

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Get all chats with filtering support
$chats = $whatsapp->getChats();
foreach ($chats->getResult() as $chat) {
    echo $chat['name'] . " - " . $chat['lastMessage']['body'] . "\n";
}

// Use the Room Controller for advanced filtering
use eseperio\whatsapp\controllers\RoomController;

$module = Yii::$app->getModule('whatsapp');
$controller = new RoomController('room', $module);

// Get all rooms
$allRooms = $controller->actionList('my-session');

// Get only group chats with new messages
Yii::$app->request->setQueryParams([
    'isGroup' => 1,
    'hasNewMessages' => 1
]);
$filteredRooms = $controller->actionIndex('my-session');

// Search rooms by name
Yii::$app->request->setQueryParams(['name' => 'family']);
$searchResults = $controller->actionIndex('my-session');
}
```

### Error Handling

```php
use eseperio\whatsapp\exceptions\WhatsAppException;

$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

try {
    $response = $whatsapp->sendTextMessage('invalid-chat-id', 'Hello');
    
    if (!$response->isSuccessful()) {
        echo "Error: " . $response->getErrorMessage() . "\n";
        echo "Status Code: " . $response->statusCode . "\n";
    }
} catch (WhatsAppException $e) {
    echo "WhatsApp API Error: " . $e->getMessage() . "\n";
}
```

## Configuration Options

### Module Configuration

```php
'modules' => [
    'whatsapp' => [
        'class' => 'eseperio\whatsapp\WhatsAppModule',
        
        // Feature toggles
        'enableSessionManagement' => true,  // Enable session operations
        'enableMessaging' => true,          // Enable message sending
        'enableContactManagement' => true,  // Enable contact operations  
        'enableGroupChat' => true,          // Enable group management
        'enableChannels' => true,           // Enable channel features
        'enableMedia' => true,              // Enable media handling
        
        // Client component configuration
        'whatsappClientConfig' => [
            'baseUrl' => 'http://localhost:3000',  // API base URL
            'apiKey' => null,                      // Optional API key
            'defaultSessionId' => 'default',       // Default session ID
            'timeout' => 30,                       // Request timeout in seconds
        ],
    ],
],
```

### Component Configuration

You can also configure the component directly:

```php
'components' => [
    'whatsapp' => [
        'class' => 'eseperio\whatsapp\components\WhatsAppClient',
        'baseUrl' => 'http://localhost:3000',
        'apiKey' => 'your-api-key',
        'defaultSessionId' => 'main',
        'timeout' => 60,
    ],
],
```

## Session Management

The library provides comprehensive session management:

```php
$whatsapp = Yii::$app->getModule('whatsapp')->whatsappClient;

// Start session
$whatsapp->startSession('session-1');

// Get session status
$status = $whatsapp->getSessionStatus('session-1');
echo $status->get('state'); // CONNECTED, UNPAIRED, etc.

// Restart session
$whatsapp->restartSession('session-1');

// Stop session
$whatsapp->stopSession('session-1');

// Terminate session completely
$whatsapp->terminateSession('session-1');

// Get all active sessions
$sessions = $whatsapp->getSessions();
```

## API Response Handling

All API methods return an `ApiResponse` object:

```php
$response = $whatsapp->sendTextMessage('1234567890@c.us', 'Hello');

// Check if successful
if ($response->isSuccessful()) {
    // Get result data
    $result = $response->getResult();
    
    // Get specific field
    $messageId = $response->get('id');
    
    // Convert to array
    $array = $response->toArray();
} else {
    // Handle error
    echo $response->getErrorMessage();
    echo $response->statusCode;
}
```

## Documentation

- [Room Management and Caching Guide](ROOM_MANAGEMENT.md) - Detailed guide for the new room management features and caching capabilities
- [avoylenko/wwebjs-api documentation](https://github.com/avoylenko/wwebjs-api) - API details
- [whatsapp-web.js documentation](https://docs.wwebjs.dev/) - WhatsApp Web concepts

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- Create an issue for bug reports or feature requests
- Check the [avoylenko/wwebjs-api documentation](https://github.com/avoylenko/wwebjs-api) for API details
- Review the [whatsapp-web.js documentation](https://docs.wwebjs.dev/) for WhatsApp Web concepts

## Disclaimer

This project is not affiliated with WhatsApp or Meta. Use at your own risk and ensure compliance with WhatsApp's Terms of Service.
