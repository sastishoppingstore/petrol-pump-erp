<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralized error handling for ERP operations.
 * Logs technical details, returns friendly user messages (Urdu/English).
 */
class ErrorHandler
{
    public static function handle(Throwable $e, string $operation = 'Unknown', string $locale = 'en'): array
    {
        $code = $e->getCode() ?: 500;
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();

        // Log technical error
        Log::error("ERP Operation Failed: {$operation}", [
            'error' => $message,
            'file' => $file,
            'line' => $line,
            'code' => $code,
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        // Return friendly user message
        $friendlyMessage = self::getFriendlyMessage($e, $locale);

        return [
            'success' => false,
            'error' => $friendlyMessage,
            'code' => $code,
            'technical' => config('app.debug') ? $message : null,
        ];
    }

    /**
     * Get a friendly error message in the user's language.
     */
    private static function getFriendlyMessage(Throwable $e, string $locale = 'en'): string
    {
        $message = $e->getMessage();
        $class = class_basename($e);

        // Map specific exceptions to friendly messages
        $messages = [
            'ValidationException' => [
                'en' => 'Please check your input and try again.',
                'ur' => 'براہ کرم اپنی معلومات دوبارہ دیکھیں۔',
            ],
            'ModelNotFoundException' => [
                'en' => 'The record you are looking for does not exist.',
                'ur' => 'یہ ریکارڈ موجود نہیں ہے۔',
            ],
            'AuthorizationException' => [
                'en' => 'You do not have permission to perform this action.',
                'ur' => 'آپ کو یہ کام کرنے کی اجازت نہیں ہے۔',
            ],
            'QueryException' => [
                'en' => 'Database error. Please try again or contact support.',
                'ur' => 'ڈیٹا بیس میں خرابی۔ براہ کرم دوبارہ کوشش کریں۔',
            ],
            'Throwable' => [
                'en' => 'An error occurred. Please try again.',
                'ur' => 'ایک خرابی واقع ہوئی۔ براہ کرم دوبارہ کوشش کریں۔',
            ],
        ];

        // Check if message contains specific keywords (from business logic)
        if (stripos($message, 'stock') !== false || stripos($message, 'shortage') !== false) {
            return $locale === 'ur' 
                ? 'اسٹاک میں کمی ہے۔' 
                : 'Insufficient stock available.';
        }

        if (stripos($message, 'credit limit') !== false) {
            return $locale === 'ur'
                ? 'کریڈٹ حد سے زیادہ ہے۔'
                : 'Credit limit exceeded.';
        }

        if (stripos($message, 'shift') !== false || stripos($message, 'closed') !== false) {
            return $locale === 'ur'
                ? 'شفٹ پہلے سے بند ہے۔'
                : 'This shift is already closed.';
        }

        if (stripos($message, 'meter') !== false || stripos($message, 'reading') !== false) {
            return $locale === 'ur'
                ? 'ریڈنگ میں خرابی ہے۔'
                : 'Meter reading validation failed.';
        }

        // Fall back to class-based message
        $key = isset($messages[$class]) ? $class : 'Throwable';
        return $messages[$key][$locale] ?? 'An error occurred. Please try again.';
    }

    /**
     * Validate business rules with friendly error messages.
     */
    public static function validateBusinessRule(bool $condition, string $errorEn, string $errorUr): void
    {
        if (!$condition) {
            $locale = session('locale', 'en');
            $message = $locale === 'ur' ? $errorUr : $errorEn;
            throw new \DomainException($message);
        }
    }
}
