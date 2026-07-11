<?php
// Default environment variables for Hritik AI

// Option 1: Google Gemini API (Recommended for free tier)
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Option 2: OpenAI API
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// Option 3: Local Ollama Model
define('OLLAMA_ENDPOINT', getenv('OLLAMA_ENDPOINT') ?: 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', getenv('OLLAMA_MODEL') ?: 'llama3');

// Select which provider to use ('gemini', 'openai', 'ollama', or 'local_php')
define('LLM_PROVIDER', getenv('LLM_PROVIDER') ?: 'gemini');
