<?php
namespace Core\API;

class ExternalIntelligence {

    /**
     * Search free online APIs to find an answer.
     */
    public function search(string $query): string {
        // Strip out conversational fillers to get core keywords
        $cleanQuery = $this->extractKeywords($query);
        
        // Tech term disambiguation (so "python" -> "python programming language")
        static $techTerms = ['python' => true, 'java' => true, 'ruby' => true, 'swift' => true, 'go' => true, 'rust' => true, 'c' => true, 'r' => true, 'php' => true, 'javascript' => true, 'html' => true, 'css' => true];
        if (isset($techTerms[strtolower($cleanQuery)])) {
            $cleanQuery = $cleanQuery . ' programming language';
        }

        // 1. Try Wikipedia first (more accurate for knowledge queries)
        $wikiAnswer = $this->queryWikipedia($cleanQuery);
        if ($wikiAnswer) {
            return $wikiAnswer;
        }

        // 2. Try DuckDuckGo Instant Answer API
        $ddgAnswer = $this->queryDuckDuckGo($cleanQuery);
        if ($ddgAnswer) {
            return $ddgAnswer;
        }
        
        return "I searched the neural networks and online archives, but I couldn't find a definitive answer for '{$cleanQuery}'.";
    }

    private function queryWikipedia(string $query): ?string {
        // Disambiguation for common tech terms
        static $techTerms = ['python' => true, 'java' => true, 'ruby' => true, 'swift' => true, 'go' => true, 'rust' => true, 'c' => true, 'r' => true];
        $queryLower = strtolower(trim($query));
        if (isset($techTerms[$queryLower])) {
            $query = $query . ' (programming language)';
        }
        
        $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . urlencode(ucwords($query));
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HritikAI/1.0 (Integration Testing)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['extract']) && !empty($data['extract'])) {
                return $data['extract'];
            }
        }
        
        return null;
    }

    private function queryDuckDuckGo(string $query): ?string {
        $url = 'https://api.duckduckgo.com/?q=' . urlencode($query) . '&format=json&no_html=1&skip_disambig=1';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['AbstractText']) && !empty($data['AbstractText'])) {
                return $data['AbstractText'];
            }
            if (isset($data['Answer']) && !empty($data['Answer'])) {
                 return $data['Answer'];
            }
        }
        
        return null;
    }

    /**
     * Simple utility to clean prompt from chatty words
     */
    private function extractKeywords(string $prompt): string {
        $prompt = strtolower(trim($prompt));
        
        $removals = [
            'what is', 'who is', 'tell me about', 'explain', 'what are', 'search for',
            'kya hai', 'kya h', 'kisne banaya', 'kon hai', 'kaun hai', 'baare mein',
            'ke baare mein', 'kahan hai', 'kidhar hai', 'kab hua'
        ];
        
        foreach ($removals as $word) {
            $prompt = str_ireplace($word, '', $prompt);
        }
        
        return trim(preg_replace('/\s+/', ' ', str_replace(['?', '!', '.'], '', $prompt)));
    }
}
