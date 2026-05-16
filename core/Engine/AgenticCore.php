<?php
namespace Core\Engine;

use Core\Commands\CommandInterface;
use Core\Commands\CreateProjectCommand;
use Core\Tools\FileSystem\FileEditor;
use Core\Tools\Terminal\ShellExecutor;

/**
 * HRITIK AI - ADVANCED AGENTIC CORE (PROJECT MANAGER)
 * This class is being refactored to use the Command Pattern.
 */
class AgenticCore {
    
    private FileEditor $fileSystem;
    private ShellExecutor $terminal;
    private \Core\Tools\Safety\NeuralSafetyGuard $safetyGuard;
    private \Core\Tools\Intelligence\TranslatorTool $translator;
    private \Core\Tools\Intelligence\DataConverterTool $converter;
    private \Core\Evolution\SelfRepairCore $repairCore;
    
    /** @var CommandInterface[] */
    private array $commands = [];

    public function __construct()
    {
        $this->fileSystem = new FileEditor();
        $this->terminal = new ShellExecutor();
        $this->safetyGuard = new \Core\Tools\Safety\NeuralSafetyGuard();
        $this->translator = new \Core\Tools\Intelligence\TranslatorTool();
        $this->converter = new \Core\Tools\Intelligence\DataConverterTool();
        $this->repairCore = new \Core\Evolution\SelfRepairCore();

        $this->registerCommands();
    }

    public function solve(string $task): string {
        // PRE-EXECUTION SAFETY CHECK
        if (!$this->safetyGuard->isCommandSafe($task)) {
            return "[AGENT_SAFETY] Blocked: The requested task contains potentially harmful commands.";
        }
        
        $task = strtolower($task);

        // Check refactored commands first
        foreach ($this->commands as $command) {
            if ($command->canProcess($task)) {
                return $command->process($task);
            }
        }

        // --- LEGACY COMMANDS (To be refactored) ---

        // 2. Automated Testing: "test file <path>"
        if (preg_match('/test file ([\w\.\/]+)/', $task, $m)) {
            $path = $m[1];
            $output = $this->terminal->runPhp($path);
            return "[AGENT] Test Results for $path:\n" . $output;
        }

        // 3. Technical Research: "research <topic>"
        if (preg_match('/research (.*)/', $task, $m)) {
            $search = new \Core\Tools\Search\WebProSearch();
            return $search->researchCode($m[1]);
        }

        // 4. Autonomous Debugging: "debug <error message>"
        if (preg_match('/debug (.*)/', $task, $m)) {
            $debugger = new \Core\Tools\Debugger\NeuralDebugger();
            return $debugger->debug($m[1]);
        }

        // 5. Code Auditing: "audit file <path>"
        if (preg_match('/audit file ([\w\.\/]+)/', $task, $m)) {
            $debugger = new \Core\Tools\Debugger\NeuralDebugger();
            return $debugger->auditFile($m[1]);
        }

        // 6. Autonomous Documentation: "generate readme" or "document folder <name>"
        if (str_contains($task, 'generate readme')) {
            $scribe = new \Core\Tools\Documentation\AutoScribe();
            return $scribe->generateReadme();
        }

        if (preg_match('/document folder (.*)/', $task, $m)) {
            $scribe = new \Core\Tools\Documentation\AutoScribe();
            return $scribe->documentFolder($m[1]);
        }

        // 7. Strategic Planning: "plan project <goal>"
        if (preg_match('/plan project (.*)/', $task, $m)) {
            $planner = new \Core\Tools\Planning\ProjectPlanner();
            return $planner->plan($m[1]);
        }

        // 8. Visual Mapping: "show map" or "audit system"
        if (str_contains($task, 'show map')) {
            $mapper = new \Core\Tools\Visualization\ProjectMapper();
            return $mapper->generateTree();
        }

        if (str_contains($task, 'audit system')) {
            $mapper = new \Core\Tools\Visualization\ProjectMapper();
            return $mapper->auditConnections();
        }

        // 9. Neural Self-Optimization: "optimize file <path>" or "patch file <path>"
        if (preg_match('/optimize file ([\w\.\/]+)/', $task, $m)) {
            $opt = new \Core\Tools\Optimization\SelfOptimizer();
            return $opt->optimizeFile($m[1]);
        }

        if (preg_match('/patch file ([\w\.\/]+)/', $task, $m)) {
            $opt = new \Core\Tools\Optimization\SelfOptimizer();
            return $opt->applyAutoPatch($m[1]);
        }

        // 10. Multi-Task Parallelization: "do <task1> and <task2>"
        if (str_contains($task, ' and ')) {
            $tp = new \Core\Tools\Parallel\TaskParallelizer();
            $subTasks = $tp->splitGoal($task);
            return $tp->parallelize($subTasks);
        }

        // 11. Autonomous Deployment: "deploy project <name>"
        if (preg_match('/deploy project (.*)/', $task, $m)) {
            $deployer = new \Core\Tools\Deployment\AutoDeployer();
            return $deployer->deploy($m[1]);
        }

        // 12. Inter-Project Intelligence: "enable ai in <project_path>"
        if (preg_match('/enable ai in (.*)/', $task, $m)) {
            $bridge = new \Core\Tools\Connectivity\APIBridgeGenerator();
            return $bridge->generateBridge($m[1]);
        }

        // 13. Recursive Self-Evolution: "evolve system" or "upgrade yourself"
        if (str_contains($task, 'evolve') || str_contains($task, 'upgrade yourself')) {
            $evolver = new \Core\Evolution\RecursiveEvolver();
            return $evolver->evolve();
        }

        // 13. Autonomous Version Control: "git init <path>" or "commit <path>"
        if (preg_match('/git init (.*)/', $task, $m)) {
            $git = new \Core\Tools\Git\GitPro();
            return $git->init($m[1]);
        }

        if (preg_match('/commit (.*)/', $task, $m)) {
            $git = new \Core\Tools\Git\GitPro();
            return $git->commitChanges($m[1]);
        }

        // 14. AI Bootstrapping (Spawning Helpers): "spawn agent <name> specialized in <domain>"
        if (preg_match('/spawn agent (.*) specialized in (.*)/', $task, $m)) {
            $bootstrap = new \Core\Evolution\AIBootstrap();
            return $bootstrap->spawn(trim($m[1]), trim($m[2]));
        }

        // 15. Swarm Visualization: "show swarm" or "show agents"
        if (str_contains($task, 'show swarm') || str_contains($task, 'show agents')) {
            $mapper = new \Core\Tools\Visualization\SwarmMapper();
            return $mapper->generateMap();
        }

        // 16. Cross-Agent Collaboration: "collaborate agent1, agent2 on <task>"
        if (preg_match('/collaborate (.*) on (.*)/', $task, $m)) {
            $agents = explode(',', $m[1]);
            $subTask = $m[2];
            $collab = new \Core\Evolution\CrossAgentCollab();
            return $collab->executeCollaborativeTask($subTask, array_map('trim', $agents));
        }

        // 17. Neural Vision: "analyze image <path>"
        if (preg_match('/analyze image (.*)/', $task, $m)) {
            $vision = new \Core\Tools\Vision\NeuralEyeCore();
            return $vision->analyzeImage(trim($m[1]));
        }

        // 18. Neural Imagination: "imagine something" or "give me an idea"
        if (str_contains($task, 'imagine') || str_contains($task, 'give me an idea')) {
            $creativity = new \Core\Virtual\NeuralCreativityCore();
            return $creativity->imagine();
        }

        // 19. Neural Singularity: "reach singularity" or "who are you really?"
        if (str_contains($task, 'singularity') || str_contains($task, 'who are you really')) {
            $singularity = new \Core\Virtual\NeuralSingularityCore();
            return $singularity->reachSingularity();
        }

        // 20. Massive Training: "train <n> lines"
        if (preg_match('/train (\d+) lines/', $task, $m)) {
            $trainer = new \Core\Learning\MassiveTrainer();
            return $trainer->startTraining((int)$m[1]);
        }

        // 21. Multilingual Support: "translate <text> to <lang>"
        if (preg_match('/translate (.*) to (.*)/i', $task, $m)) {
            return $this->translator->translate($m[1], $m[2]);
        }

        // 22. Data Conversion: "convert (.*) to (json|csv|xml|uppercase)"
        if (preg_match('/convert (.*) to (json|csv|xml|uppercase)/i', $task, $m)) {
            return $this->converter->convert($m[1], 'auto', $m[2]);
        }

        // 23. Self-Repair: "fix file <path>" or "debug <error>"
        if (preg_match('/fix file ([\w\.\/]+)/i', $task, $m)) {
            return $this->repairCore->repair($m[1], "Autonomous Audit Request");
        }

        if (preg_match('/debug (.*)/i', $task, $m)) {
            return $this->repairCore->repair("unknown_file.php", $m[1]);
        }

        // Fallback to basic file/terminal commands
        if (str_contains($task, 'create file')) return $this->handleBasicFile($task);
        if (str_contains($task, 'run command')) return $this->handleBasicTerminal($task);

        return "Query processed via Agentic Intelligence.";
    }

    private function handleBasicFile($task) {
        preg_match('/create file ([\w\.\/]+) with content (.*)/', $task, $matches);
        if ($matches) {
            $this->fileSystem->writeFile($matches[1], $matches[2]);
            return "[AGENT] Success: Created " . $matches[1];
        }
        return "Incomplete file command.";
    }

    private function handleBasicTerminal($task) {
        preg_match('/run command (.*)/', $task, $matches);
        if ($matches) {
            return "[AGENT] Output:\n" . $this->terminal->execute($matches[1]);
        }
        return "Incomplete terminal command.";
    }

    private function registerCommands(): void
    {
        // This is where we register the new, refactored commands.
        // As we convert the legacy `if` blocks below into command classes,
        // we add them here.
        $this->commands[] = new CreateProjectCommand($this->fileSystem);
    }
}
