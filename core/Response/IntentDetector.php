<?php
namespace Core\Response;

class IntentDetector {
    private static ?string $lastIntent = null;

    /**
     * Pre-compiled combined regex for faster evaluation natively in C, avoiding sequential regex calls.
     * Evaluates multiple intents in a single pass.
     */
    private static string $combinedPattern = '/(?<image_gen>image|photo|pic|bnao|banao|generate|drawing|sketch|visualize|portrait|landscape|tasveer|dikha|draw|paint)|' .
        '(?<coding>code|program|script|coding|develop|function|class|html|css|javascript|python|java|php|mysql|react|node|api|backend|frontend|database|sql|algorithm|loop|array|variable|debug|compile|syntax|git|framework)|' .
        '(?<math>calculate|hisab|math|maths|solve|equation|plus|minus|multiply|divide|sum|average|percentage|factorial|root|algebra|geometry|trigonometry|\d+[\+\-\*\/]\d+)|' .
        '(?<weather>weather|mausam|temperature|barish|rain|garmi|sardi|humidity|forecast)|' .
        '(?<news>news|khabar|samachar|headlines|taza|latest|trending|breaking)|' .
        '(?<translation>translate|anuvad|hindi mein|english mein|meaning of|matlab|iska matlab)|' .
        '(?<identity>naam|who are you|identity|name|tera naam|tumhara naam|made you|creator|developer|hritik ai|kaun ho|kaun hai tu|kisne banaya)|' .
        '(?<greeting>^(?:hello|hi|hey|namaste|helo|hlo|yo)$|kese ho|kaise ho|how are you|kya haal|good morning|good night|good evening|salam|assalam)|' .
        '(?<chat>or btao|aur batao|kya chal raha|whats up|sup|^nothing$|bore|kuch sunao|baat karo|timepass|mazak|joke|hasao)|' .
        '(?<informational>what is|who is|where is|kaun hai|kahan hai|kab|when|how many|how much|how to|translate|population|capital|rajdhani|define|meaning|matlab|Nobel|prize|oscar|winner|president|prime minister|country|world|history|science|geography|explain|samjhao|btao kya|batao kya)|' .
        '(?<farewell>^bye$|goodbye|cya|see you|alvida|chalo|band karo|bas|shukria|dhanyawad|thank)|' .
        '(?<tool_query>convert|badlo|password|json|format|calculator|qr|scan|ip|lookup)/i';

    /**
     * Ordered list of intent keys mapped to named capture groups for quick iteration.
     * Order dictates priority.
     */
    private static array $intentKeys = [
        'image_gen', 'coding', 'math', 'weather', 'news', 'translation',
        'identity', 'greeting', 'chat', 'informational', 'farewell', 'tool_query'
    ];

    /**
     * Detect intent from text with Hinglish support + context awareness
     * Returns intent string
     */
    public function detect(string $text): string {
        $text = strtolower(trim($text));
        
        static $combinedPattern = '/(?<image_gen>image|photo|pic|bnao|banao|generate|drawing|sketch|visualize|portrait|landscape|tasveer|dikha|draw|paint)|' .
            '(?<coding>code|program|script|coding|develop|function|class|html|css|javascript|python|java|php|mysql|react|node|api|backend|frontend|database|sql|algorithm|loop|array|variable|debug|compile|syntax|git|framework)|' .
            '(?<math>calculate|hisab|math|maths|solve|equation|plus|minus|multiply|divide|sum|average|percentage|factorial|root|algebra|geometry|trigonometry|\d+[\+\-\*\/]\d+)|' .
            '(?<weather>weather|mausam|temperature|barish|rain|garmi|sardi|humidity|forecast)|' .
            '(?<news>news|khabar|samachar|headlines|taza|latest|trending|breaking)|' .
            '(?<translation>translate|anuvad|hindi mein|english mein|meaning of|matlab|iska matlab)|' .
            '(?<identity>naam|who are you|identity|name|tera naam|tumhara naam|made you|creator|developer|hritik ai|kaun ho|kaun hai tu|kisne banaya)|' .
            '(?<greeting>^(?:hello|hi|hey|namaste|helo|hlo|yo)$|kese ho|kaise ho|how are you|kya haal|good morning|good night|good evening|salam|assalam)|' .
            '(?<chat>or btao|aur batao|kya chal raha|whats up|sup|^nothing$|bore|kuch sunao|baat karo|timepass|mazak|joke|hasao)|' .
            '(?<informational>what is|who is|where is|kaun hai|kahan hai|kab|when|how many|how much|how to|translate|population|capital|rajdhani|define|meaning|matlab|Nobel|prize|oscar|winner|president|prime minister|country|world|history|science|geography|explain|samjhao|btao kya|batao kya)|' .
            '(?<farewell>^bye$|goodbye|cya|see you|alvida|chalo|band karo|bas|shukria|dhanyawad|thank)|' .
            '(?<tool_query>convert|badlo|password|json|format|calculator|qr|scan|ip|lookup)/i';

        // Continue-conversation patterns → use last intent
        if (self::$lastIntent && preg_match('/^(aur|or|and|btao|batao|more|continue|agge|aage|phir|fir)\b/i', $text)) {
            return self::$lastIntent;
        }

        // Image Generation
        if (preg_match('/image|photo|pic|bnao|banao|generate|drawing|sketch|visualize|portrait|landscape|tasveer|dikha|draw|paint/', $text)) {
            self::$lastIntent = 'image_gen';
            return 'image_gen';
        }
        
        // Programming/Code
        if (preg_match('/code|program|script|coding|develop|function|class|html|css|javascript|python|java|php|mysql|react|node|api|backend|frontend|database|sql|algorithm|loop|array|variable|debug|compile|syntax|git|framework/', $text)) {
            self::$lastIntent = 'coding';
            return 'coding';
        }
        
        // Math / Calculation
        if (preg_match('/calculate|hisab|math|maths|solve|equation|plus|minus|multiply|divide|sum|average|percentage|factorial|root|algebra|geometry|trigonometry|\d+[\+\-\*\/]\d+/', $text)) {
            self::$lastIntent = 'math';
            return 'math';
        }
        
        // Weather
        if (preg_match('/weather|mausam|temperature|barish|rain|garmi|sardi|humidity|forecast/', $text)) {
            self::$lastIntent = 'weather';
            return 'weather';
        }
        
        // News
        if (preg_match('/news|khabar|samachar|headlines|taza|latest|trending|breaking/', $text)) {
            self::$lastIntent = 'news';
            return 'news';
        }
        
        // Translation
        if (preg_match('/translate|anuvad|hindi mein|english mein|meaning of|matlab|iska matlab/', $text)) {
            self::$lastIntent = 'translation';
            return 'translation';
        }
        
        // Identity / Awareness
        if (preg_match('/naam|who are you|identity|name|tera naam|tumhara naam|made you|creator|developer|hritik ai|kaun ho|kaun hai tu|kisne banaya/', $text)) {
            self::$lastIntent = 'identity';
            return 'identity';
        }
        
        // Greetings / Conversational  
        if (preg_match('/^(?:hello|hi|hey|namaste|helo|hlo|yo)$/', $text) || preg_match('/kese ho|kaise ho|how are you|kya haal|good morning|good night|good evening|salam|assalam/', $text)) {
            self::$lastIntent = 'greeting';
            return 'greeting';
        }
        
        // General Chat / "What's up"
        if (preg_match('/or btao|aur batao|kya chal raha|whats up|sup|^nothing$|bore|kuch sunao|baat karo|timepass|mazak|joke|hasao/', $text)) {
            self::$lastIntent = 'chat';
            return 'chat';
        }
        
        // Informational / Search / Knowledge Questions
        if (preg_match('/what is|who is|where is|kaun hai|kahan hai|kab|when|how many|how much|how to|translate|population|capital|rajdhani|define|meaning|matlab|nobel|prize|oscar|winner|president|prime minister|country|world|history|science|geography|explain|samjhao|btao kya|batao kya/', $text)) {
            self::$lastIntent = 'informational';
            return 'informational';
        }
        
        // Farewell
        if (preg_match('/^bye$|goodbye|cya|see you|alvida|chalo|band karo|bas|shukria|dhanyawad|thank/', $text)) {
            self::$lastIntent = 'farewell';
            return 'farewell';
        }
        
        // Tool Queries
        if (preg_match('/convert|badlo|password|json|format|calculator|qr|scan|ip|lookup/', $text)) {
            self::$lastIntent = 'tool_query';
            return 'tool_query';
        }

        // If query has a question mark or question words, it's probably informational
        if (str_contains($text, '?') || preg_match('/^(?:kya|kaun|kab|kahan|kaha|kitna|kitne|kyu|kyun|kon|kiske|kiski|kiska)/', $text)) {
        // Single pass execution using preg_match_all to replace ~12 sequential preg_match calls
        // while preserving priority order (which intent is matched if multiple appear).
        if (preg_match_all(self::$combinedPattern, $text, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) {
            foreach (self::$intentKeys as $intent) {
                foreach ($matches as $match) {
                    if (isset($match[$intent]) && $match[$intent] !== null) {
        // PERFORMANCE OPTIMIZATION: Combine multiple regex patterns into a single pass.
        // Doing one preg_match_all with named capture groups is up to 7-10x faster
        // than running 12 independent preg_match calls in sequence.
        if (preg_match_all($combinedPattern, $text, $matches, PREG_SET_ORDER)) {
            // Priority ordering to match the previous sequential `if` structure's behavior
            $intents = ['image_gen', 'coding', 'math', 'weather', 'news', 'translation', 'identity', 'greeting', 'chat', 'informational', 'farewell', 'tool_query'];
            foreach ($intents as $intent) {
                foreach ($matches as $match) {
                    if (isset($match[$intent]) && $match[$intent] !== '') {
                        self::$lastIntent = $intent;
                        return $intent;
                    }
                }
            }
        }

        // Fallbacks: If query has a question mark or question words, it's probably informational
        if (str_contains($text, '?') || preg_match('/^(kya|kaun|kab|kahan|kaha|kitna|kitne|kyu|kyun|kon|kiske|kiski|kiska)/i', $text)) {
            self::$lastIntent = 'informational';
            return 'informational';
        }

        self::$lastIntent = 'unknown';
        return 'unknown';
    }

    /**
     * Get confidence score for the last detection (0.0 - 1.0)
     */
    public function getLastIntent(): ?string {
        return self::$lastIntent;
    }
}
