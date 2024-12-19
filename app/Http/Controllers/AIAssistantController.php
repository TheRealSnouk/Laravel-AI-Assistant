<?php

namespace App\Http\Controllers;

use App\Services\AI\AIModelService;
use App\Services\AI\PromptService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIAssistantController extends Controller
{
    public function __construct(
        private AIModelService $aiService,
        private PromptService $promptService,
        private SubscriptionService $subscriptionService
    ) {
        // Apply subscription middleware to relevant routes
        $this->middleware('subscription.feature:code_completion')->only(['complete']);
        $this->middleware('subscription.feature:code_review')->only(['review']);
        $this->middleware('subscription.feature:refactoring')->only(['refactor']);
        $this->middleware('subscription.feature:documentation')->only(['document']);
        $this->middleware('subscription.feature:testing')->only(['generateTests']);
        $this->middleware('subscription.feature:security_analysis')->only(['analyzeSecurity']);
    }

    public function index()
    {
        return view('welcome');
    }

    /**
     * Get code completion suggestions
     */
    public function complete(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'context' => 'nullable|array'
            ]);

            $prompt = $this->promptService->enhancePrompt(
                $request->code,
                array_merge($request->context ?? [], ['language' => $request->language])
            );

            $completion = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $completion['usage']['total_tokens']);

            return response()->json([
                'completion' => $completion['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($completion['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Code completion error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to generate code completion', $e);
        }
    }

    /**
     * Review code for improvements
     */
    public function review(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string'
            ]);

            $prompt = $this->promptService->getPredefinedPrompts()['code_review']['prompt'];
            $prompt = $this->promptService->enhancePrompt($prompt, [
                'code' => $request->code,
                'language' => $request->language
            ]);

            $review = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $review['usage']['total_tokens']);

            return response()->json([
                'review' => $review['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($review['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Code review error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to review code', $e);
        }
    }

    /**
     * Suggest code refactoring
     */
    public function refactor(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'focus' => 'nullable|string'
            ]);

            $prompt = $this->promptService->getPredefinedPrompts()['refactor']['prompt'];
            if ($request->focus) {
                $prompt .= "\nFocus on: {$request->focus}";
            }

            $prompt = $this->promptService->enhancePrompt($prompt, [
                'code' => $request->code,
                'language' => $request->language
            ]);

            $refactoring = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $refactoring['usage']['total_tokens']);

            return response()->json([
                'refactoring' => $refactoring['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($refactoring['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Code refactoring error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to generate refactoring suggestions', $e);
        }
    }

    /**
     * Generate code documentation
     */
    public function document(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'style' => 'nullable|string|in:jsdoc,phpdoc,docstring,markdown'
            ]);

            $prompt = $this->promptService->getPredefinedPrompts()['document']['prompt'];
            if ($request->style) {
                $prompt .= "\nStyle: {$request->style}";
            }

            $prompt = $this->promptService->enhancePrompt($prompt, [
                'code' => $request->code,
                'language' => $request->language
            ]);

            $documentation = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $documentation['usage']['total_tokens']);

            return response()->json([
                'documentation' => $documentation['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($documentation['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Documentation generation error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to generate documentation', $e);
        }
    }

    /**
     * Generate test cases
     */
    public function generateTests(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'framework' => 'nullable|string'
            ]);

            $prompt = $this->promptService->getPredefinedPrompts()['test']['prompt'];
            if ($request->framework) {
                $prompt .= "\nFramework: {$request->framework}";
            }

            $prompt = $this->promptService->enhancePrompt($prompt, [
                'code' => $request->code,
                'language' => $request->language
            ]);

            $tests = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $tests['usage']['total_tokens']);

            return response()->json([
                'tests' => $tests['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($tests['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Test generation error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to generate tests', $e);
        }
    }

    /**
     * Analyze code for security issues
     */
    public function analyzeSecurity(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'framework' => 'nullable|string'
            ]);

            $prompt = "Analyze this code for security vulnerabilities, including but not limited to:\n" .
                     "- SQL Injection\n" .
                     "- XSS (Cross-site Scripting)\n" .
                     "- CSRF (Cross-site Request Forgery)\n" .
                     "- Authentication Issues\n" .
                     "- Authorization Issues\n" .
                     "- Data Exposure\n" .
                     "- Input Validation\n" .
                     "Provide specific recommendations for each issue found.";

            if ($request->framework) {
                $prompt .= "\nFramework: {$request->framework}";
            }

            $prompt = $this->promptService->enhancePrompt($prompt, [
                'code' => $request->code,
                'language' => $request->language
            ]);

            $analysis = $this->aiService->complete($prompt);
            
            // Record token usage
            $this->subscriptionService->recordUsage('token', $analysis['usage']['total_tokens']);

            return response()->json([
                'analysis' => $analysis['choices'][0]['text'],
                'suggestions' => $this->promptService->generateSuggestions($analysis['choices'][0]['text'])
            ]);

        } catch (\Exception $e) {
            Log::error('Security analysis error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to analyze security', $e);
        }
    }

    /**
     * Save code snippet
     */
    public function saveSnippet(Request $request)
    {
        try {
            if (!$this->subscriptionService->canUseFeature('save_snippets')) {
                return response()->json([
                    'error' => 'Subscription required',
                    'message' => 'Saving snippets requires a paid subscription',
                    'upgrade_url' => route('subscription.index')
                ], 402);
            }

            if ($this->subscriptionService->hasReachedLimit('snippets')) {
                return response()->json([
                    'error' => 'Snippet limit reached',
                    'message' => 'You have reached your saved snippets limit',
                    'upgrade_url' => route('subscription.index')
                ], 429);
            }

            $request->validate([
                'code' => 'required|string',
                'language' => 'required|string',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $snippet = auth()->user()->snippets()->create([
                'title' => $request->title,
                'description' => $request->description,
                'code' => $request->code,
                'language' => $request->language
            ]);

            $this->subscriptionService->recordUsage('snippet');

            return response()->json([
                'message' => 'Snippet saved successfully',
                'snippet' => $snippet
            ]);

        } catch (\Exception $e) {
            Log::error('Save snippet error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to save snippet', $e);
        }
    }

    /**
     * Error response helper
     */
    private function errorResponse(string $message, \Exception $e)
    {
        return response()->json([
            'error' => $message,
            'details' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
