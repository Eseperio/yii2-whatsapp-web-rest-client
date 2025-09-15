# Console Commands

The WhatsApp Web REST Client provides console commands for managing sessions and performing basic operations from the command line.

## Setup

To use the console commands, add the WhatsApp module to your console application configuration:

```php
// config/console.php
return [
    'modules' => [
        'whatsapp' => [
            'class' => 'eseperio\whatsapp\WhatsAppModule',
            'whatsappClientConfig' => [
                'baseUrl' => 'http://localhost:3000',
                'apiKey' => 'your-api-key', // if required
                'defaultSessionId' => 'console',
                'timeout' => 30,
            ],
        ],
    ],
    'controllerMap' => [
        'whatsapp' => 'eseperio\whatsapp\commands\WhatsAppController',
    ],
];
```

## Available Commands

### Session Management

#### Start a Session
```bash
php yii whatsapp/session/start [sessionId]
```

Starts a new WhatsApp session. If no session ID is provided, uses the default.

Example:
```bash
php yii whatsapp/session/start my-session
```

#### Check Session Status
```bash
php yii whatsapp/session/status [sessionId]
```

Gets the current status of a session.

Example:
```bash
php yii whatsapp/session/status my-session
```

#### Get QR Code
```bash
php yii whatsapp/session/qr [sessionId]
```

Retrieves the QR code for session authentication.

Example:
```bash
php yii whatsapp/session/qr my-session
```

#### Stop Session
```bash
php yii whatsapp/session/stop [sessionId]
```

Stops (logs out) a session.

Example:
```bash
php yii whatsapp/session/stop my-session
```

#### List All Sessions
```bash
php yii whatsapp/session/list
```

Lists all active sessions with their status.

### Messaging

#### Send Text Message
```bash
php yii whatsapp/message/send <chatId> <message> [sessionId]
```

Sends a text message to a chat. The chat ID can be a phone number (will be automatically formatted) or a full WhatsApp ID.

Examples:
```bash
# Using phone number
php yii whatsapp/message/send 1234567890 "Hello World" my-session

# Using full WhatsApp ID
php yii whatsapp/message/send "1234567890@c.us" "Hello World" my-session

# Using default session
php yii whatsapp/message/send 1234567890 "Hello World"
```

### Information

#### List Contacts
```bash
php yii whatsapp/contact/list [sessionId]
```

Lists contacts for the session (shows first 10).

Example:
```bash
php yii whatsapp/contact/list my-session
```

#### Health Check
```bash
php yii whatsapp/ping
```

Checks if the WhatsApp Web REST API is responsive.

## Options

### Global Options

- `--sessionId, -s`: Set the default session ID
- `--verbose, -v`: Enable verbose output

Examples:
```bash
php yii whatsapp/session/status -s my-session
php yii whatsapp/message/send 1234567890 "Hello" -v
```

## Workflow Examples

### Complete Session Setup

1. **Start a new session:**
   ```bash
   php yii whatsapp/session/start my-session
   ```

2. **Get QR code for authentication:**
   ```bash
   php yii whatsapp/session/qr my-session
   ```

3. **Scan the QR code with your WhatsApp mobile app**

4. **Check if session is connected:**
   ```bash
   php yii whatsapp/session/status my-session
   ```

5. **Send a test message:**
   ```bash
   php yii whatsapp/message/send 1234567890 "Hello from console!" my-session
   ```

### Monitoring Sessions

```bash
# Check all sessions
php yii whatsapp/session/list

# Check specific session
php yii whatsapp/session/status my-session

# Check API health
php yii whatsapp/ping
```

### Batch Operations

You can use these commands in shell scripts for automation:

```bash
#!/bin/bash

# Check if session is connected
STATUS=$(php yii whatsapp/session/status my-session | grep "CONNECTED")

if [ -n "$STATUS" ]; then
    echo "Session is connected, sending messages..."
    php yii whatsapp/message/send 1234567890 "Daily report message" my-session
else
    echo "Session not connected, starting session..."
    php yii whatsapp/session/start my-session
fi
```

## Error Handling

The console commands return appropriate exit codes:

- `0`: Success
- `1`: Error

You can use these in scripts:

```bash
if php yii whatsapp/session/start my-session; then
    echo "Session started successfully"
    php yii whatsapp/message/send 1234567890 "Session ready!" my-session
else
    echo "Failed to start session"
    exit 1
fi
```

## Troubleshooting

### Common Issues

1. **"Module 'whatsapp' not found"**
   - Make sure the module is configured in your console application
   - Check that the module class path is correct

2. **"API is not responding"**
   - Verify the WhatsApp Web REST API container is running
   - Check the `baseUrl` configuration
   - Test with `php yii whatsapp/ping`

3. **"Session not found"**
   - Check if the session exists with `php yii whatsapp/session/list`
   - Start the session with `php yii whatsapp/session/start`

4. **"Failed to send message"**
   - Verify the session is connected
   - Check the chat ID format
   - Ensure the recipient number is registered on WhatsApp

### Debug Mode

Use the `--verbose` flag to get more detailed output:

```bash
php yii whatsapp/message/send 1234567890 "Test" -v
```

This will show:
- Request details
- Response information
- Error stack traces (if any)

## Integration with Cron Jobs

You can use these commands in cron jobs for automated operations:

```bash
# Send daily report at 9 AM
0 9 * * * /path/to/project/yii whatsapp/message/send 1234567890 "Daily report ready" report-session

# Check session health every hour
0 * * * * /path/to/project/yii whatsapp/session/status monitor-session

# Restart session if needed
0 */6 * * * /path/to/project/scripts/check-and-restart-session.sh
```

## Advanced Usage

### Custom Session IDs

Use meaningful session IDs for different purposes:

```bash
# Customer service session
php yii whatsapp/session/start customer-service

# Marketing session  
php yii whatsapp/session/start marketing

# Internal notifications
php yii whatsapp/session/start internal-alerts
```

### Message Templates

Create shell functions for common messages:

```bash
# In your .bashrc or script
send_order_confirmation() {
    local phone="$1"
    local order_id="$2"
    php yii whatsapp/message/send "$phone" "Order $order_id confirmed! Thank you for your purchase." customer-service
}

send_order_confirmation 1234567890 "ORD-12345"
```