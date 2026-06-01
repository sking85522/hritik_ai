<?php
class IntentDetector {
    public function detectOriginal(string $text) {
        $text = strtolower(trim($text));

        if (preg_match('/^(aur|or|and|btao|batao|more|continue|agge|aage|phir|fir)\b/i', $text)) return 'continue';
        if (preg_match('/(image|photo|pic|bnao|banao|generate|drawing|sketch|visualize|portrait|landscape|tasveer|dikha|draw|paint)/i', $text)) return 'image_gen';
        if (preg_match('/(code|program|script|coding|develop|function|class|html|css|javascript|java|php|mysql|react|node|api|backend|frontend|database|sql|algorithm|loop|array|variable|debug|compile|syntax|git|framework)/i', $text)) return 'coding';
        if (preg_match('/(calculate|hisab|math|maths|solve|equation|plus|minus|multiply|divide|sum|average|percentage|factorial|root|algebra|geometry|trigonometry|\d+[\+\-\*\/]\d+)/i', $text)) return 'math';
        if (preg_match('/(weather|mausam|temperature|barish|rain|garmi|sardi|humidity|forecast)/i', $text)) return 'weather';
        if (preg_match('/(news|khabar|samachar|headlines|taza|latest|trending|breaking)/i', $text)) return 'news';
        if (preg_match('/(translate|anuvad|hindi mein|english mein|meaning of|matlab|iska matlab)/i', $text)) return 'translation';
        if (preg_match('/(naam|who are you|identity|name|tera naam|tumhara naam|made you|creator|developer|hritik ai|kaun ho|kaun hai tu|kisne banaya)/i', $text)) return 'identity';
        if (preg_match('/^(hello|hi|hey|namaste|helo|hlo|yo)$/i', $text) || preg_match('/(kese ho|kaise ho|how are you|kya haal|good morning|good night|good evening|salam|assalam)/i', $text)) return 'greeting';
        if (preg_match('/(or btao|aur batao|kya chal raha|whats up|sup|^nothing$|bore|kuch sunao|baat karo|timepass|mazak|joke|hasao)/i', $text)) return 'chat';
        if (preg_match('/(what is|who is|where is|kaun hai|kahan hai|kab|when|how many|how much|how to|translate|population|capital|rajdhani|define|meaning|matlab|Nobel|prize|oscar|winner|president|prime minister|country|world|history|science|geography|explain|samjhao|btao kya|batao kya)/i', $text)) return 'informational';
        if (preg_match('/(^bye$|goodbye|cya|see you|alvida|chalo|band karo|bas|shukria|dhanyawad|thank)/i', $text)) return 'farewell';
        if (preg_match('/(convert|badlo|password|json|format|calculator|qr|scan|ip|lookup)/i', $text)) return 'tool_query';
        if (preg_match('/^(kya|kaun|kab|kahan|kaha|kitna|kitne|kyu|kyun|kon|kiske|kiski|kiska)/i', $text)) return 'informational';

        return 'unknown';
    }

    public function detectOptimizedArray(string $text) {
        $text = strtolower(trim($text));

        if (preg_match('/^(?:aur|or|and|btao|batao|more|continue|agge|aage|phir|fir)\b/', $text)) return 'continue';
        if (preg_match('/image|photo|pic|bnao|banao|generate|drawing|sketch|visualize|portrait|landscape|tasveer|dikha|draw|paint/', $text)) return 'image_gen';
        if (preg_match('/code|program|script|coding|develop|function|class|html|css|javascript|java|php|mysql|react|node|api|backend|frontend|database|sql|algorithm|loop|array|variable|debug|compile|syntax|git|framework/', $text)) return 'coding';
        if (preg_match('/calculate|hisab|math|maths|solve|equation|plus|minus|multiply|divide|sum|average|percentage|factorial|root|algebra|geometry|trigonometry|\d+[\+\-\*\/]\d+/', $text)) return 'math';
        if (preg_match('/weather|mausam|temperature|barish|rain|garmi|sardi|humidity|forecast/', $text)) return 'weather';
        if (preg_match('/news|khabar|samachar|headlines|taza|latest|trending|breaking/', $text)) return 'news';
        if (preg_match('/translate|anuvad|hindi mein|english mein|meaning of|matlab|iska matlab/', $text)) return 'translation';
        if (preg_match('/naam|who are you|identity|name|tera naam|tumhara naam|made you|creator|developer|hritik ai|kaun ho|kaun hai tu|kisne banaya/', $text)) return 'identity';
        if (preg_match('/^(?:hello|hi|hey|namaste|helo|hlo|yo)$/', $text) || preg_match('/kese ho|kaise ho|how are you|kya haal|good morning|good night|good evening|salam|assalam/', $text)) return 'greeting';
        if (preg_match('/or btao|aur batao|kya chal raha|whats up|sup|^nothing$|bore|kuch sunao|baat karo|timepass|mazak|joke|hasao/', $text)) return 'chat';
        if (preg_match('/what is|who is|where is|kaun hai|kahan hai|kab|when|how many|how much|how to|translate|population|capital|rajdhani|define|meaning|matlab|nobel|prize|oscar|winner|president|prime minister|country|world|history|science|geography|explain|samjhao|btao kya|batao kya/', $text)) return 'informational';
        if (preg_match('/^bye$|goodbye|cya|see you|alvida|chalo|band karo|bas|shukria|dhanyawad|thank/', $text)) return 'farewell';
        if (preg_match('/convert|badlo|password|json|format|calculator|qr|scan|ip|lookup/', $text)) return 'tool_query';

        // Fix the str_contains issue: only informational if it contains '?' OR matches the exact regex
        if (str_contains($text, '?') || preg_match('/^(?:kya|kaun|kab|kahan|kaha|kitna|kitne|kyu|kyun|kon|kiske|kiski|kiska)/', $text)) {
            return 'informational';
        }

        return 'unknown';
    }
}

$a = new IntentDetector();

$queries = ["some completely random text that doesn't trigger anything whatsoever ok", "here is another one just for testing", "what does this string do", "maybe it works, maybe it doesn't", "weather forecast today", "calculate 5+5", "can you draw a cat", "bye see you", "hello how are you", "what is the capital of india"];

for ($i = 0; $i < 100; $i++) {
    $queries[] = "what is the completely randomly generated text about some random stuff ok";
}

$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    foreach ($queries as $q) {
        $a->detectOriginal($q);
    }
}
$end = microtime(true);
echo "Original mix heavy miss: " . ($end - $start) . "s\n";

$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    foreach ($queries as $q) {
        $a->detectOptimizedArray($q);
    }
}
$end = microtime(true);
echo "Optimized ARRAY WITHOUT ANY CAPTURING BRACKETS: " . ($end - $start) . "s\n";

$mismatches = 0;
foreach ($queries as $q) {
    if ($a->detectOriginal($q) !== $a->detectOptimizedArray($q)) {
        echo "$q: " . $a->detectOriginal($q) . " vs " . $a->detectOptimizedArray($q) . "\n";
        $mismatches++;
    }
}
echo "Mismatches: " . $mismatches . "\n";
