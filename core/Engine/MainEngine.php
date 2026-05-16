<?php
namespace Core\Engine;

use Core\NLP\NLPPipeline;
use Core\Memory\Storage\OnlineMemoryBridge;
use Core\Memory\RAG\LocalRAG;
use Core\Evaluation\ConfidenceScorer;
use Core\Training\Feedback\LocalTeacherBridge;
use Core\Training\Feedback\FeedbackLoop;
use Core\SQLGenerator\SQLGenerator;

/**
 * HRITIK AI - MAIN ENGINE (SYSTEMIC REFACTOR)
 * The central orchestrator now moved into the Engine folder.
 */
class MainEngine {
    private $nlp;
    private $memory;
    private $mood;
    private $tools;
    private $agenticCore;
    private $moduleIntegrator;
    private $sqlGen;
    private LocalRAG $rag;
    private ConfidenceScorer $confidenceScorer;
    private LocalTeacherBridge $localTeacher;
    private FeedbackLoop $feedbackLoop;
    private $personalMemory;
    
    private array $history = [];

    public function __construct() {
        $this->nlp = new NLPPipeline();
        $this->memory = new OnlineMemoryBridge();
        $this->mood = new \Core\NLP\NeuralMoodAnalyzer();
        $this->tools = new \Core\Tools\ToolRegistry();
        $this->agenticCore = new AgenticCore();
        $this->moduleIntegrator = new ModuleIntegrator();
        $this->sqlGen = new SQLGenerator();
        $this->rag = new LocalRAG();
        $this->confidenceScorer = new ConfidenceScorer();
        $this->localTeacher = new LocalTeacherBridge();
        $this->feedbackLoop = new FeedbackLoop($this->rag);
        $this->personalMemory = new \Core\Memory\Storage\PersonalMemoryBridge();
    }

    public function processPrompt(string $prompt, string $sessionId = 'default', $onToken = null): array {
        $prompt = trim($prompt);
        if (empty($prompt)) {
            return ['status' => 'success', 'response' => '', 'intent' => 'empty'];
        }

        $analysis = $this->nlp->process($prompt);
        $intent = $analysis['intent'] ?? 'general';
        $response = null;
        $source = 'none';
        $evidence = [];

        // 0. Permanent Memory Recall (Personal Context)
        $personalContext = $this->personalMemory->recall($prompt);
        if ($personalContext) {
            $prompt = "[User Context: " . trim($personalContext) . "] " . $prompt;
        }

        // 1. Check SQL Knowledge (Dynamic)
        $sql = $this->sqlGen->generate('search_knowledge', ['query' => $prompt]);
        if ($sql) {
            $dbRes = $this->queryDB($sql);
            if (!empty($dbRes)) {
                $response = $dbRes[0]['k_value'];
                $source = 'dynamic_sql_knowledge';
            }
        }

        // 2. Check RAG
        if (!$response) {
            $ragRes = $this->rag->answer($prompt);
            if ($ragRes) {
                $response = $ragRes['answer'];
                $source = 'rag';
            }
        }

        // 3. Fallback to Memory
        if (!$response) {
            $sql = $this->sqlGen->generate('get_memory', ['prompt' => $prompt]);
            $dbRes = $this->queryDB($sql);
            if (!empty($dbRes)) {
                $response = $dbRes[0]['response'];
                $source = 'dynamic_sql_memory';
            }
        }

        // 4. Online Search (Wikipedia / DuckDuckGo)
        if (!$response || $this->confidenceScorer->score($prompt, $response, $source) < 0.4) {
            require_once dirname(__DIR__) . '/API/ExternalIntelligence.php';
            $external = new \Core\API\ExternalIntelligence();
            $searchRes = $external->search($prompt);
            
            if ($searchRes && !str_contains($searchRes, "I searched the neural networks")) {
                $response = $searchRes;
                $source = 'online_search_api';
            }
        }

        // 5. Neural Reasoning & Teacher Refinement (Filter/Think)
        $confidence = $this->confidenceScorer->score($prompt, $response, $source);
        if ($confidence < 0.7 || !$response || $response === "N/A") {
            $teacherRes = $this->localTeacher->evaluate($prompt, (string)$response, $this->history, $confidence);
            if (($teacherRes['status'] ?? '') === 'success' && !empty($teacherRes['final_answer'])) {
                $response = $teacherRes['final_answer'];
                $source = 'neural_local_teacher';
            }
        }

        // 6. Absolute Final Attempt (Forced Neural Creative Generation)
        if (!$response || $response === "N/A" || strlen($response) < 5) {
            $teacherRes = $this->localTeacher->evaluate($prompt, "FORCE_GENERATE", $this->history, 0);
            if (($teacherRes['status'] ?? '') === 'success' && !empty($teacherRes['final_answer'])) {
                $response = $teacherRes['final_answer'];
                $source = 'forced_neural_generation';
            } else {
                // LAST RESORT: If Teacher is DEAD/CRASHING, use Creativity Core
                $creativity = new \Core\Virtual\NeuralCreativityCore();
                $response = $creativity->imagine();
                $source = 'creativity_fallback';
            }
        }

        // Ensure we NEVER send a generic "Maaf kijiye" unless the API is dead
        if (!$response) {
            $response = "[Neural Link Error] Thinking..."; 
        }

        // Finalize
        $this->feedbackLoop->record($prompt, $prompt, $response, ['source' => $source]);
        if ($source === 'neural_local_teacher' || $source === 'forced_neural_generation') {
            // Check if it's a JSON project plan
            $decoded = json_decode($response, true);
            if (is_array($decoded) && !empty($decoded)) {
                $architect = new \Core\Tools\Intelligence\ProjectArchitect();
                $response = $architect->build($decoded);
                $source = 'neural_project_architect';
            }
        }

        $this->history[] = ['prompt' => $prompt, 'response' => $response];
        
        // Log to History via SQL Gen
        $logSql = $this->sqlGen->generate('save_history', [
            'session_id' => $sessionId,
            'prompt' => $prompt,
            'response' => $response,
            'intent' => $intent
        ]);
        $this->queryDB($logSql);

        // 7. Permanent Learning (Save Personal Facts)
        $this->personalMemory->learn($prompt, $response);

        return [
            'status' => 'success',
            'response' => $response,
            'intent' => $intent,
            'source' => $source,
            'confidence' => $confidence
        ];
    }

    private function queryDB(string $sql): array {
        global $db;
        if (!isset($db) || $db === null) return [];
        $res = $db->query($sql);
        return $res['data'] ?? [];
    }
}
