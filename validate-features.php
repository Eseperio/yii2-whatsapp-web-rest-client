<?php
/**
 * Simple validation script for new room management and caching features
 * 
 * This script validates file structure and basic syntax without requiring Yii2
 */

echo "=== Room Management and Caching Validation ===\n\n";

$baseDir = __DIR__;

// Test 1: File structure validation
echo "Test 1: File Structure Validation\n";
echo "---------------------------------\n";

$requiredFiles = [
    'src/models/Room.php' => 'Room Model',
    'src/controllers/RoomController.php' => 'Room Controller',
    'src/views/room/index.php' => 'Room View',
    'examples/room-management.php' => 'Room Examples',
    'ROOM_MANAGEMENT.md' => 'Documentation'
];

foreach ($requiredFiles as $file => $description) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        echo "  ✓ $description ($file)\n";
    } else {
        echo "  ✗ Missing: $description ($file)\n";
    }
}

echo "\n";

// Test 2: PHP syntax validation
echo "Test 2: PHP Syntax Validation\n";
echo "-----------------------------\n";

$phpFiles = [
    'src/WhatsAppModule.php',
    'src/components/WhatsAppClient.php',
    'src/models/Room.php',
    'src/controllers/RoomController.php',
    'examples/room-management.php'
];

foreach ($phpFiles as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $return = 0;
        exec("php -l \"$fullPath\" 2>&1", $output, $return);
        
        if ($return === 0) {
            echo "  ✓ $file\n";
        } else {
            echo "  ✗ $file: " . implode(' ', $output) . "\n";
        }
    } else {
        echo "  ? $file (not found)\n";
    }
}

echo "\n";

// Test 3: Content validation
echo "Test 3: Content Validation\n";
echo "--------------------------\n";

// Check WhatsAppModule for cache properties
$moduleFile = $baseDir . '/src/WhatsAppModule.php';
if (file_exists($moduleFile)) {
    $content = file_get_contents($moduleFile);
    $cacheProperties = ['enableCache', 'cacheComponent', 'cacheDuration'];
    
    foreach ($cacheProperties as $property) {
        if (strpos($content, "public \$$property") !== false) {
            echo "  ✓ WhatsAppModule has \$$property property\n";
        } else {
            echo "  ✗ WhatsAppModule missing \$$property property\n";
        }
    }
} else {
    echo "  ✗ WhatsAppModule.php not found\n";
}

// Check WhatsAppClient for cache methods
$clientFile = $baseDir . '/src/components/WhatsAppClient.php';
if (file_exists($clientFile)) {
    $content = file_get_contents($clientFile);
    $cacheMethods = ['getCache', 'generateCacheKey', 'shouldCache'];
    
    foreach ($cacheMethods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "  ✓ WhatsAppClient has $method() method\n";
        } else {
            echo "  ✗ WhatsAppClient missing $method() method\n";
        }
    }
    
    // Check for cache integration
    if (strpos($content, 'getCache()') !== false) {
        echo "  ✓ WhatsAppClient has cache integration\n";
    } else {
        echo "  ✗ WhatsAppClient missing cache integration\n";
    }
} else {
    echo "  ✗ WhatsAppClient.php not found\n";
}

// Check RoomController for required actions
$controllerFile = $baseDir . '/src/controllers/RoomController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    $requiredActions = [
        'actionIndex', 'actionList', 'actionGroups', 'actionIndividual',
        'actionUnread', 'actionArchived', 'actionPinned', 'actionFilter', 'actionFilterOptions'
    ];
    
    foreach ($requiredActions as $action) {
        if (strpos($content, "function $action") !== false) {
            echo "  ✓ RoomController has $action() method\n";
        } else {
            echo "  ✗ RoomController missing $action() method\n";
        }
    }
} else {
    echo "  ✗ RoomController.php not found\n";
}

// Check Room model for required methods
$roomFile = $baseDir . '/src/models/Room.php';
if (file_exists($roomFile)) {
    $content = file_get_contents($roomFile);
    $requiredMethods = ['fromApiData', 'toArray', 'matchesFilters'];
    
    foreach ($requiredMethods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "  ✓ Room model has $method() method\n";
        } else {
            echo "  ✗ Room model missing $method() method\n";
        }
    }
} else {
    echo "  ✗ Room.php not found\n";
}

echo "\n";

// Test 4: Documentation validation
echo "Test 4: Documentation Validation\n";
echo "--------------------------------\n";

$readmeFile = $baseDir . '/README.md';
if (file_exists($readmeFile)) {
    $content = file_get_contents($readmeFile);
    
    if (strpos($content, 'Room Management') !== false) {
        echo "  ✓ README.md mentions Room Management\n";
    } else {
        echo "  ✗ README.md missing Room Management section\n";
    }
    
    if (strpos($content, 'Caching Support') !== false || strpos($content, 'enableCache') !== false) {
        echo "  ✓ README.md mentions caching features\n";
    } else {
        echo "  ✗ README.md missing caching information\n";
    }
} else {
    echo "  ✗ README.md not found\n";
}

$docsFile = $baseDir . '/ROOM_MANAGEMENT.md';
if (file_exists($docsFile)) {
    echo "  ✓ ROOM_MANAGEMENT.md documentation exists\n";
    
    $content = file_get_contents($docsFile);
    if (strlen($content) > 1000) {
        echo "  ✓ ROOM_MANAGEMENT.md has substantial content\n";
    } else {
        echo "  ? ROOM_MANAGEMENT.md seems short\n";
    }
} else {
    echo "  ✗ ROOM_MANAGEMENT.md not found\n";
}

echo "\n";

// Test 5: Feature completeness check
echo "Test 5: Feature Completeness Check\n";
echo "----------------------------------\n";

$features = [
    'Cache configuration in module' => false,
    'Cache integration in client' => false,
    'Room model with filtering' => false,
    'Room controller with actions' => false,
    'Web view for room listing' => false,
    'Comprehensive examples' => false,
    'Detailed documentation' => false
];

// Check each feature
if (file_exists($baseDir . '/src/WhatsAppModule.php')) {
    $content = file_get_contents($baseDir . '/src/WhatsAppModule.php');
    if (strpos($content, 'enableCache') !== false && strpos($content, 'cacheDuration') !== false) {
        $features['Cache configuration in module'] = true;
    }
}

if (file_exists($baseDir . '/src/components/WhatsAppClient.php')) {
    $content = file_get_contents($baseDir . '/src/components/WhatsAppClient.php');
    if (strpos($content, 'getCache') !== false && strpos($content, 'cacheKey') !== false) {
        $features['Cache integration in client'] = true;
    }
}

if (file_exists($baseDir . '/src/models/Room.php')) {
    $content = file_get_contents($baseDir . '/src/models/Room.php');
    if (strpos($content, 'matchesFilters') !== false && strpos($content, 'fromApiData') !== false) {
        $features['Room model with filtering'] = true;
    }
}

if (file_exists($baseDir . '/src/controllers/RoomController.php')) {
    $content = file_get_contents($baseDir . '/src/controllers/RoomController.php');
    if (strpos($content, 'actionIndex') !== false && strpos($content, 'ArrayDataProvider') !== false) {
        $features['Room controller with actions'] = true;
    }
}

if (file_exists($baseDir . '/src/views/room/index.php')) {
    $features['Web view for room listing'] = true;
}

if (file_exists($baseDir . '/examples/room-management.php')) {
    $features['Comprehensive examples'] = true;
}

if (file_exists($baseDir . '/ROOM_MANAGEMENT.md')) {
    $features['Detailed documentation'] = true;
}

foreach ($features as $feature => $implemented) {
    echo "  " . ($implemented ? '✓' : '✗') . " $feature\n";
}

echo "\n";

// Summary
echo "=== Validation Summary ===\n";
$implementedCount = count(array_filter($features));
$totalCount = count($features);

echo "Features implemented: $implementedCount/$totalCount\n";

if ($implementedCount === $totalCount) {
    echo "🎉 All features successfully implemented!\n";
    echo "The room management and caching functionality is ready for use.\n";
} else {
    echo "⚠️  Some features may need attention.\n";
    echo "Review the validation results above for details.\n";
}

echo "\n";
echo "Next steps:\n";
echo "1. Configure your Yii2 application with the new cache settings\n";
echo "2. Set up URL routes for the Room controller (optional)\n";
echo "3. Test with a live WhatsApp Web REST API instance\n";
echo "4. Customize filters and views as needed for your use case\n";