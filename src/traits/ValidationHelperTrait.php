<?php

namespace eseperio\whatsapp\traits;

use eseperio\whatsapp\models\ApiResponse;

/**
 * Validation Helper Trait
 * 
 * Provides validation methods for WhatsApp operations
 * 
 * @author E.Alamo
 * @package eseperio\whatsapp\traits
 */
trait ValidationHelperTrait
{
    /**
     * Validate WhatsApp number format
     * 
     * @param string $number Phone number
     * @return bool
     */
    public function isValidWhatsAppNumber($number)
    {
        // Remove all non-digit characters
        $cleanNumber = preg_replace('/\D/', '', $number);
        
        // Check if it's a valid length (7-15 digits according to ITU-T E.164)
        return strlen($cleanNumber) >= 7 && strlen($cleanNumber) <= 15;
    }

    /**
     * Format number to WhatsApp ID format
     * 
     * @param string $number Phone number
     * @param string $suffix Suffix to add (@c.us for individual, @g.us for group)
     * @return string
     */
    public function formatToWhatsAppId($number, $suffix = '@c.us')
    {
        // Remove all non-digit characters
        $cleanNumber = preg_replace('/\D/', '', $number);
        
        // Add suffix if not already present
        if (!str_contains($cleanNumber, '@')) {
            $cleanNumber .= $suffix;
        }
        
        return $cleanNumber;
    }

    /**
     * Extract number from WhatsApp ID
     * 
     * @param string $whatsappId WhatsApp ID (e.g., "1234567890@c.us")
     * @return string
     */
    public function extractNumberFromId($whatsappId)
    {
        return str_replace(['@c.us', '@g.us', '@s.whatsapp.net'], '', $whatsappId);
    }

    /**
     * Check if chat ID is a group
     * 
     * @param string $chatId Chat ID
     * @return bool
     */
    public function isGroupChat($chatId)
    {
        return str_contains($chatId, '@g.us');
    }

    /**
     * Check if chat ID is an individual chat
     * 
     * @param string $chatId Chat ID
     * @return bool
     */
    public function isIndividualChat($chatId)
    {
        return str_contains($chatId, '@c.us');
    }

    /**
     * Validate media data for sending
     * 
     * @param array $mediaData Media data array
     * @return bool
     */
    public function isValidMediaData($mediaData)
    {
        if (!is_array($mediaData)) {
            return false;
        }
        
        // Check required fields
        $requiredFields = ['mimetype', 'data'];
        foreach ($requiredFields as $field) {
            if (!isset($mediaData[$field]) || empty($mediaData[$field])) {
                return false;
            }
        }
        
        // Validate base64 data
        if (!base64_decode($mediaData['data'], true)) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @return bool
     */
    public function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if session ID is valid format
     * 
     * @param string $sessionId Session ID
     * @return bool
     */
    public function isValidSessionId($sessionId)
    {
        // Session ID should be alphanumeric with hyphens and underscores allowed
        return preg_match('/^[a-zA-Z0-9_-]+$/', $sessionId) === 1;
    }

    /**
     * Validate latitude and longitude coordinates
     * 
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return bool
     */
    public function isValidCoordinates($latitude, $longitude)
    {
        return (
            is_numeric($latitude) && 
            is_numeric($longitude) &&
            $latitude >= -90 && $latitude <= 90 &&
            $longitude >= -180 && $longitude <= 180
        );
    }

    /**
     * Validate poll options
     * 
     * @param array $options Poll options
     * @return bool
     */
    public function isValidPollOptions($options)
    {
        if (!is_array($options) || empty($options)) {
            return false;
        }
        
        // WhatsApp supports up to 12 poll options
        if (count($options) > 12) {
            return false;
        }
        
        // Each option should be a non-empty string
        foreach ($options as $option) {
            if (!is_string($option) || empty(trim($option))) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Sanitize text for WhatsApp message
     * 
     * @param string $text Text to sanitize
     * @param int $maxLength Maximum length (WhatsApp limit is ~65,536 characters)
     * @return string
     */
    public function sanitizeMessageText($text, $maxLength = 4096)
    {
        // Remove null bytes and other control characters
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        // Truncate if too long
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text;
    }
}