<?php
/**
 * Room Controller usage examples
 * 
 * This file demonstrates how to use the RoomController for listing and filtering WhatsApp chats
 */

use eseperio\whatsapp\controllers\RoomController;
use eseperio\whatsapp\exceptions\WhatsAppException;

// Example 1: Basic room listing
echo "=== Example 1: Basic Room Listing ===\n";

try {
    // You would typically access this through web routes, but here's a programmatic example
    $module = Yii::$app->getModule('whatsapp');
    $controller = new RoomController('room', $module);
    
    // Get all rooms
    $result = $controller->actionList('demo-session');
    
    if ($result['success']) {
        echo "Found {$result['pagination']['totalCount']} rooms:\n";
        
        foreach ($result['data'] as $room) {
            $type = $room['isGroup'] ? 'Group' : 'Individual';
            $unread = $room['unreadCount'] > 0 ? " ({$room['unreadCount']} unread)" : '';
            echo "- {$room['name']} [$type]{$unread}\n";
        }
    } else {
        echo "Error: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Filter groups with new messages
echo "=== Example 2: Groups with New Messages ===\n";

try {
    // Simulate request parameters for filtering
    Yii::$app->request->setQueryParams([
        'isGroup' => 1,
        'hasNewMessages' => 1,
        'per-page' => 10
    ]);
    
    $result = $controller->actionIndex('demo-session');
    
    if ($result['success']) {
        echo "Found {$result['pagination']['totalCount']} groups with new messages:\n";
        
        foreach ($result['data'] as $room) {
            echo "- {$room['name']} ({$room['unreadCount']} unread messages)\n";
            echo "  Last message: {$room['lastMessage']['body'] ?? 'No message'}\n";
        }
    } else {
        echo "Error: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Search rooms by name
echo "=== Example 3: Search Rooms by Name ===\n";

try {
    // Search for rooms containing "family" in the name
    Yii::$app->request->setQueryParams([
        'name' => 'family',
        'per-page' => 5
    ]);
    
    $result = $controller->actionIndex('demo-session');
    
    if ($result['success']) {
        echo "Found {$result['pagination']['totalCount']} rooms matching 'family':\n";
        
        foreach ($result['data'] as $room) {
            $type = $room['isGroup'] ? 'Group' : 'Individual';
            echo "- {$room['name']} [$type]\n";
            echo "  Last activity: {$room['timestamp']}\n";
        }
    } else {
        echo "Error: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Get filter options
echo "=== Example 4: Available Filter Options ===\n";

try {
    $filterOptions = $controller->actionFilterOptions();
    
    if ($filterOptions['success']) {
        echo "Available filters:\n";
        
        foreach ($filterOptions['data']['availableFilters'] as $filter => $config) {
            echo "- $filter ({$config['type']}): {$config['description']}\n";
            if (isset($config['example'])) {
                echo "  Example: $filter={$config['example']}\n";
            }
        }
        
        echo "\nUsage examples:\n";
        foreach ($filterOptions['data']['examples'] as $description => $example) {
            echo "- $description: $example\n";
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 5: Complex filtering using POST
echo "=== Example 5: Complex Filtering ===\n";

try {
    // Simulate POST request with complex filters
    $_POST['filters'] = [
        'isGroup' => true,
        'minUnreadCount' => 2,
        'isMuted' => false
    ];
    
    Yii::$app->request->setQueryParams(['per-page' => 5]);
    
    $result = $controller->actionFilter('demo-session');
    
    if ($result['success']) {
        echo "Found {$result['pagination']['totalCount']} groups with 2+ unread messages (not muted):\n";
        
        foreach ($result['data'] as $room) {
            echo "- {$room['name']} ({$room['unreadCount']} unread)\n";
            echo "  Muted: " . ($room['isMuted'] ? 'Yes' : 'No') . "\n";
            echo "  Pinned: " . ($room['isPinned'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "Error: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Room Controller Examples Complete ===\n";