<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define the features and limits for each subscription tier
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free Tier',
            'price' => 0,
            'features' => [
                'code_completion' => false,
                'code_review' => true,
                'refactoring' => false,
                'documentation' => true,
                'testing' => false,
                'security_analysis' => false,
                'save_snippets' => false,
                'gpt4' => false,
                'codex' => false
            ],
            'limits' => [
                'requests_per_day' => 10,
                'tokens_per_request' => 1000,
                'saved_snippets' => 0
            ]
        ],
        'basic' => [
            'name' => 'Basic',
            'price' => 9.99,
            'features' => [
                'code_completion' => true,
                'code_review' => true,
                'refactoring' => true,
                'documentation' => true,
                'testing' => false,
                'security_analysis' => false,
                'save_snippets' => true,
                'gpt4' => false,
                'codex' => true
            ],
            'limits' => [
                'requests_per_day' => 50,
                'tokens_per_request' => 2000,
                'saved_snippets' => 100
            ]
        ],
        'pro' => [
            'name' => 'Professional',
            'price' => 29.99,
            'features' => [
                'code_completion' => true,
                'code_review' => true,
                'refactoring' => true,
                'documentation' => true,
                'testing' => true,
                'security_analysis' => true,
                'save_snippets' => true,
                'gpt4' => true,
                'codex' => true
            ],
            'limits' => [
                'requests_per_day' => 200,
                'tokens_per_request' => 4000,
                'saved_snippets' => 500
            ]
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 99.99,
            'features' => [
                'code_completion' => true,
                'code_review' => true,
                'refactoring' => true,
                'documentation' => true,
                'testing' => true,
                'security_analysis' => true,
                'save_snippets' => true,
                'gpt4' => true,
                'codex' => true
            ],
            'limits' => [
                'requests_per_day' => 1000,
                'tokens_per_request' => 8000,
                'saved_snippets' => -1 // unlimited
            ],
            'custom_features' => [
                'priority_support',
                'custom_models',
                'team_collaboration',
                'api_access'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Descriptions
    |--------------------------------------------------------------------------
    |
    | Detailed descriptions of each feature for display in the UI
    |
    */

    'features' => [
        'code_completion' => [
            'name' => 'Code Completion',
            'description' => 'Real-time intelligent code suggestions and completion',
            'icon' => 'code'
        ],
        'code_review' => [
            'name' => 'Code Review',
            'description' => 'AI-powered code review with best practice suggestions',
            'icon' => 'check-circle'
        ],
        'refactoring' => [
            'name' => 'Code Refactoring',
            'description' => 'Smart suggestions for code improvement and modernization',
            'icon' => 'refresh'
        ],
        'documentation' => [
            'name' => 'Documentation Generator',
            'description' => 'Auto-generate comprehensive code documentation',
            'icon' => 'book'
        ],
        'testing' => [
            'name' => 'Test Generation',
            'description' => 'Generate test cases and testing scenarios',
            'icon' => 'flask'
        ],
        'security_analysis' => [
            'name' => 'Security Analysis',
            'description' => 'Identify potential security issues and vulnerabilities',
            'icon' => 'shield'
        ],
        'save_snippets' => [
            'name' => 'Save Code Snippets',
            'description' => 'Save and organize useful code snippets',
            'icon' => 'save'
        ],
        'gpt4' => [
            'name' => 'GPT-4 Access',
            'description' => 'Access to the most advanced AI model',
            'icon' => 'star'
        ],
        'codex' => [
            'name' => 'Codex Access',
            'description' => 'Access to specialized code generation model',
            'icon' => 'code-branch'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking API usage and limits
    |
    */

    'tracking' => [
        'warning_threshold' => 0.8, // Warn when 80% of limit is reached
        'cache_duration' => 3600,   // Cache usage data for 1 hour
        'reset_period' => 'daily'   // When usage limits reset
    ],
];
