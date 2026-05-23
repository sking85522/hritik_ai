# Hritik AI - Continuous Background Training Pipeline

This folder contains scripts to train Hritik AI continuously using large datasets (such as those from HuggingFace).

## `train_pipeline.php`

This script streams and chunks large datasets to bypass PHP's memory limits, ensuring your AI can learn from infinite amounts of text without crashing.

### Supported Formats
- `.jsonl` (JSON Lines - standard for HuggingFace datasets)
- `.txt` (Plain text)

### How to use with HuggingFace Datasets

1. Download a dataset in JSONL format from Hugging Face.
   *Example:*
   ```bash
   wget "https://huggingface.co/datasets/angrygiraffe/claude-opus-4.6-4.7-reasoning-8.7k/resolve/main/full_train.jsonl?download=true" -O claude_opus.jsonl
   ```

2. Run the pipeline script:
   ```bash
   php scripts/train_pipeline.php --file=claude_opus.jsonl --type=jsonl
   ```

3. **Background Processing:** If you want it to run continuously in the background, you can use screen, tmux, or background operators.
   ```bash
   php scripts/train_pipeline.php --file=claude_opus.jsonl --type=jsonl > training.log 2>&1 &
   ```

The script extracts `text`, `content`, or `conversations` keys from standard HuggingFace JSONL formats and injects them directly into Hritik AI's Markov memory (`core/GenerativeAI/neural_corpus.txt`).
