# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of Yii2 WhatsApp Web REST Client
- WhatsApp module with configurable features
- WhatsApp client component with comprehensive API methods
- Session management functionality (start, stop, restart, status)
- Message operations (text, media, location, contact, poll messages)
- Group chat management (create, participants, settings)
- Contact management (block/unblock, profile information)
- Message interactions (reply, react, delete, download media)
- Chat presence features (typing, recording indicators)
- Input validation and sanitization helpers
- Message formatting helpers
- Error handling with custom exceptions
- Comprehensive examples and documentation
- Integration patterns for common use cases
- Rate limiting and retry mechanisms
- Multiple session support
- Configurable feature toggles

### Features
- Support for all major WhatsApp Web REST API endpoints
- Robust error handling and response models
- Helper traits for common operations
- Validation helpers for input data
- Message formatting utilities
- Session state monitoring
- Environment-specific configuration
- Broadcast messaging capabilities
- Media validation and handling
- Poll creation and management
- Group administration features
- Contact information retrieval
- QR code authentication support
- Comprehensive logging and debugging

### Documentation
- Complete README with installation and usage instructions
- Configuration examples
- Usage examples for all features
- Integration patterns and best practices
- Session management guidelines
- Error handling strategies
- Performance optimization tips

## [1.0.0] - 2025-01-15

### Added
- Initial stable release
- Full compatibility with avoylenko/wwebjs-api docker container
- Support for Yii2 2.0.14+
- PHP 7.4+ compatibility
- Comprehensive test coverage
- Production-ready configuration options