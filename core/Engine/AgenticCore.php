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

    private $nluModel;

    public function __construct()
    {
        $this->initializeNLU();
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

        // 1. Parallel Sub-Task Checking first
        if (str_contains($task, ' and ')) {
            $tp = new \Core\Tools\Parallel\TaskParallelizer();
            $subTasks = $tp->splitGoal($task);
            return $tp->parallelize($subTasks);
        }

        // 2. Exact command matching for refactored commands
        foreach ($this->commands as $command) {
            if ($command->canProcess($task)) {
                return $command->process($task);
            }
        }

        // 3. Fallback to basic commands
        if (str_contains($task, 'create file')) return $this->handleBasicFile($task);
        if (str_contains($task, 'run command')) return $this->handleBasicTerminal($task);

        // --- NLU INTENT PIPELINE ---

        $intent = null;
        if ($this->nluModel) {
            $intent = $this->nluModel->predict($task);
        }

        // Fallback to legacy regex if intent couldn't be accurately identified
        if (!$intent) {
            return "Query couldn't be understood. Try rephrasing.";
        }

        // Execute logic based on detected Intent (and extract entities if needed)
        switch ($intent) {
            case 'test_file':
                if (preg_match('/([\w\.\/]+\.\w+)/', $task, $m)) {
                    $path = $m[1];
                    $output = $this->terminal->runPhp($path);
                    return "[AGENT] Test Results for $path:\n" . $output;
                }
                return "Which file should I test?";

            case 'research':
                $topic = trim(str_replace(['research about', 'tell me about', 'research'], '', $task));
                $search = new \Core\Tools\Search\WebProSearch();
                return $search->researchCode($topic);

            case 'debug':
                $error = trim(str_replace(['debug this error', 'debug', 'fix the bug'], '', $task));
                $debugger = new \Core\Tools\Debugger\NeuralDebugger();
                return $debugger->debug($error);

            case 'audit_file':
                if (preg_match('/([\w\.\/]+\.\w+)/', $task, $m)) {
                    $debugger = new \Core\Tools\Debugger\NeuralDebugger();
                    return $debugger->auditFile($m[1]);
                }
                return "Which file should I audit?";

            case 'generate_readme':
                $scribe = new \Core\Tools\Documentation\AutoScribe();
                return $scribe->generateReadme();

            case 'document_folder':
                if (preg_match('/folder (.*)/', $task, $m) || preg_match('/directory (.*)/', $task, $m)) {
                    $scribe = new \Core\Tools\Documentation\AutoScribe();
                    return $scribe->documentFolder(trim($m[1]));
                }
                return "Which folder should I document?";

            case 'plan_project':
                $goal = trim(str_replace(['plan project', 'create a plan for'], '', $task));
                $planner = new \Core\Tools\Planning\ProjectPlanner();
                return $planner->plan($goal);

            case 'show_map':
                $mapper = new \Core\Tools\Visualization\ProjectMapper();
                return $mapper->generateTree();

            case 'audit_system':
                $mapper = new \Core\Tools\Visualization\ProjectMapper();
                return $mapper->auditConnections();

            case 'optimize_file':
                if (preg_match('/([\w\.\/]+\.\w+)/', $task, $m)) {
                    $opt = new \Core\Tools\Optimization\SelfOptimizer();
                    return $opt->optimizeFile($m[1]);
                }
                return "Which file should I optimize?";

            case 'patch_file':
                if (preg_match('/([\w\.\/]+\.\w+)/', $task, $m)) {
                    $opt = new \Core\Tools\Optimization\SelfOptimizer();
                    return $opt->applyAutoPatch($m[1]);
                }
                return "Which file should I patch?";

            case 'deploy_project':
                $project = trim(str_replace(['deploy project', 'deploy'], '', $task));
                $deployer = new \Core\Tools\Deployment\AutoDeployer();
                return $deployer->deploy($project);

            case 'enable_ai':
                if (preg_match('/in (.*)/', $task, $m) || preg_match('/to (.*)/', $task, $m)) {
                    $bridge = new \Core\Tools\Connectivity\APIBridgeGenerator();
                    return $bridge->generateBridge(trim($m[1]));
                }
                return "Where should I enable the AI?";

            case 'evolve':
                $evolver = new \Core\Evolution\RecursiveEvolver();
                return $evolver->evolve();

            case 'git_init':
                if (preg_match('/([\w\.\/]+)/', str_replace('git init', '', $task), $m)) {
                    $git = new \Core\Tools\Git\GitPro();
                    return $git->init(trim($m[1]));
                }
                return "Provide a path for git init.";

            case 'commit':
                if (preg_match('/([\w\.\/]+)/', str_replace(['commit changes', 'commit'], '', $task), $m)) {
                    $git = new \Core\Tools\Git\GitPro();
                    return $git->commitChanges(trim($m[1]));
                }
                return "Provide a path to commit.";

            case 'spawn_agent':
                if (preg_match('/agent (.*) specialized in (.*)/', $task, $m)) {
                    $bootstrap = new \Core\Evolution\AIBootstrap();
                    return $bootstrap->spawn(trim($m[1]), trim($m[2]));
                }
                return "Provide agent name and specialization domain.";

            case 'show_swarm':
                $mapper = new \Core\Tools\Visualization\SwarmMapper();
                return $mapper->generateMap();

            case 'collaborate':
                if (preg_match('/collaborate (.*) on (.*)/', $task, $m)) {
                    $agents = explode(',', $m[1]);
                    $collab = new \Core\Evolution\CrossAgentCollab();
                    return $collab->executeCollaborativeTask(trim($m[2]), array_map('trim', $agents));
                }
                return "Provide agents and the task to collaborate on.";

            case 'analyze_image':
                if (preg_match('/([\w\.\/]+\.(png|jpg|jpeg|gif))/', $task, $m)) {
                    $vision = new \Core\Tools\Vision\NeuralEyeCore();
                    return $vision->analyzeImage($m[1]);
                }
                return "Please provide a valid image path to analyze.";

            case 'imagine':
                $creativity = new \Core\Virtual\NeuralCreativityCore();
                return $creativity->imagine();

            case 'singularity':
                $singularity = new \Core\Virtual\NeuralSingularityCore();
                return $singularity->reachSingularity();

            case 'train':
                if (preg_match('/(\d+)/', $task, $m)) {
                    $trainer = new \Core\Learning\MassiveTrainer();
                    return $trainer->startTraining((int)$m[1]);
                }
                return "How many lines should I train on?";

            case 'translate':
                if (preg_match('/translate (.*) to (.*)/i', $task, $m)) {
                    return $this->translator->translate($m[1], $m[2]);
                }
                return "Provide text and target language.";

            case 'convert':
                if (preg_match('/convert (.*) to (json|csv|xml|uppercase)/i', $task, $m)) {
                    return $this->converter->convert($m[1], 'auto', $m[2]);
                }
                return "Provide text and format to convert to.";

            default:
                return "Intent detected as: $intent, but no handler exists yet.";
        }
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

    private function initializeNLU(): void
    {
        if (class_exists('\NLPHP\Classification\NaiveBayes')) {
            $this->nluModel = new \NLPHP\Classification\NaiveBayes();

            $texts = [
                "test file", "please test this file", "run test for", "execute unit test",
                "research about", "tell me about", "find out about", "research topic",
                "debug this error", "fix the bug", "analyze error message", "debug",
                "audit the file", "review code in", "check file quality", "audit file",
                "generate readme", "create documentation", "document this project", "generate a readme file",
                "document folder", "document the directory", "create docs for folder",
                "plan project", "create a plan for", "outline project steps", "plan out",
                "show map", "show project structure", "visualize tree", "show tree",
                "audit system", "check system connections", "audit connections",
                "optimize file", "make this code better", "refactor file", "optimize",
                "patch file", "apply patch to", "fix code in file", "auto patch",
                "deploy project", "release project", "deploy application", "push to prod",
                "enable ai in", "bridge api to", "integrate ai with",
                "evolve system", "upgrade yourself", "become smarter", "evolve",
                "git init", "initialize repository", "start git",
                "commit changes", "save work", "git commit",
                "spawn agent", "create new agent", "make an assistant",
                "show swarm", "list agents", "show all agents",
                "collaborate on", "work together on", "agents collaborate",
                "analyze image", "look at picture", "vision analyze",
                "imagine something", "give me an idea", "be creative", "imagine",
                "reach singularity", "who are you really", "what is your true purpose",
                "train lines", "start training", "learn massive data",
                "translate to", "convert language", "translate text",
                "convert to", "change format to", "transform into"
            ];

            $labels = [
                "test_file", "test_file", "test_file", "test_file",
                "research", "research", "research", "research",
                "debug", "debug", "debug", "debug",
                "audit_file", "audit_file", "audit_file", "audit_file",
                "generate_readme", "generate_readme", "generate_readme", "generate_readme",
                "document_folder", "document_folder", "document_folder",
                "plan_project", "plan_project", "plan_project", "plan_project",
                "show_map", "show_map", "show_map", "show_map",
                "audit_system", "audit_system", "audit_system",
                "optimize_file", "optimize_file", "optimize_file", "optimize_file",
                "patch_file", "patch_file", "patch_file", "patch_file",
                "deploy_project", "deploy_project", "deploy_project", "deploy_project",
                "enable_ai", "enable_ai", "enable_ai",
                "evolve", "evolve", "evolve", "evolve",
                "git_init", "git_init", "git_init",
                "commit", "commit", "commit",
                "spawn_agent", "spawn_agent", "spawn_agent",
                "show_swarm", "show_swarm", "show_swarm",
                "collaborate", "collaborate", "collaborate",
                "analyze_image", "analyze_image", "analyze_image",
                "imagine", "imagine", "imagine", "imagine",
                "singularity", "singularity", "singularity",
                "train", "train", "train",
                "translate", "translate", "translate",
                "convert", "convert", "convert"
            ];

            $this->nluModel->fit($texts, $labels);
        }
    }

    private function registerCommands(): void
    {
        // This is where we register the new, refactored commands.
        // As we convert the legacy `if` blocks below into command classes,
        // we add them here.
        $this->commands[] = new CreateProjectCommand($this->fileSystem);
    }
}
