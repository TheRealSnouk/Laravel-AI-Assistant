<?php

namespace App\Services\AI;

class PromptService
{
    /**
     * Enhance user prompt with coding context
     */
    public function enhancePrompt(string $message, array $context = []): string
    {
        $enhancedPrompt = $message;

        // Add file context if available
        if (!empty($context['file'])) {
            $enhancedPrompt = "File: {$context['file']}\n\n" . $enhancedPrompt;
        }

        // Add language context
        if (!empty($context['language'])) {
            $enhancedPrompt = "Language: {$context['language']}\n\n" . $enhancedPrompt;
        }

        // Add framework context
        if (!empty($context['framework'])) {
            $enhancedPrompt = "Framework: {$context['framework']}\n\n" . $enhancedPrompt;
        }

        // Add code context if available
        if (!empty($context['code'])) {
            $enhancedPrompt = "Related Code:\n```\n{$context['code']}\n```\n\n" . $enhancedPrompt;
        }

        return $this->addPromptGuidelines($enhancedPrompt);
    }

    /**
     * Generate follow-up suggestions based on AI response
     */
    public function generateSuggestions(string $aiResponse): array
    {
        $suggestions = [];

        // Code improvement suggestion
        if (str_contains($aiResponse, 'code')) {
            $suggestions[] = "Would you like me to suggest improvements to this code?";
        }

        // Testing suggestion
        if (str_contains($aiResponse, 'function') || str_contains($aiResponse, 'class')) {
            $suggestions[] = "Would you like me to generate test cases for this code?";
        }

        // Documentation suggestion
        if (strlen($aiResponse) > 500) {
            $suggestions[] = "Would you like me to generate documentation for this solution?";
        }

        // Performance suggestion
        if (str_contains($aiResponse, 'database') || str_contains($aiResponse, 'query')) {
            $suggestions[] = "Would you like me to analyze the performance implications?";
        }

        // Security suggestion
        if (str_contains($aiResponse, 'user') || str_contains($aiResponse, 'input')) {
            $suggestions[] = "Would you like me to review security considerations?";
        }

        return array_slice($suggestions, 0, 3); // Limit to top 3 suggestions
    }

    /**
     * Add guidelines to the prompt for better AI responses
     */
    private function addPromptGuidelines(string $prompt): string
    {
        return $prompt . "\n\nPlease provide:\n" .
               "1. Clear, production-ready code\n" .
               "2. Brief explanation of the solution\n" .
               "3. Any relevant security considerations\n" .
               "4. Best practices and Laravel conventions\n";
    }

    /**
     * Get predefined prompts for common tasks
     */
    public function getPredefinedPrompts(): array
    {
        return [
            'code_review' => [
                'title' => 'Code Review',
                'prompt' => "Please review this code for:\n" .
                           "- Best practices\n" .
                           "- Security issues\n" .
                           "- Performance optimizations\n" .
                           "- Code style"
            ],
            'refactor' => [
                'title' => 'Refactor Code',
                'prompt' => "Please suggest refactoring improvements for:\n" .
                           "- Better organization\n" .
                           "- Improved maintainability\n" .
                           "- Enhanced performance\n" .
                           "- Modern PHP/Laravel features"
            ],
            'test' => [
                'title' => 'Generate Tests',
                'prompt' => "Please generate test cases including:\n" .
                           "- Unit tests\n" .
                           "- Feature tests\n" .
                           "- Edge cases\n" .
                           "- Common scenarios"
            ],
            'document' => [
                'title' => 'Generate Documentation',
                'prompt' => "Please generate documentation including:\n" .
                           "- Method descriptions\n" .
                           "- Parameter details\n" .
                           "- Return value information\n" .
                           "- Usage examples"
            ]
        ];
    }
}
