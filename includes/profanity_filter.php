<?php
class ProfanityFilter {
    private static $profanityList = [
        'fuck', 'shit', 'bitch', 'ass', 'damn', 'bastard', 'cunt', 'dick', 'piss',
        // Add more profanity words as needed
    ];

    public static function hasProfanity($text) {
        $text = strtolower($text);
        foreach (self::$profanityList as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function highlightProfanity($text) {
        $text = htmlspecialchars($text);
        foreach (self::$profanityList as $word) {
            $pattern = '/(' . preg_quote($word, '/') . ')/i';
            $text = preg_replace($pattern, '<span class="w3-red w3-padding-small">$1</span>', $text);
        }
        return $text;
    }
} 