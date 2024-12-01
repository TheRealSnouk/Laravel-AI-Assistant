# Laravel AI Assistant

A modern, cyberpunk-themed AI coding assistant powered by Anthropic's Claude-2 through Open Router API.

## Features

- Advanced AI Code Assistant using Claude-2
- Cyberpunk-themed UI with neon aesthetics
- Real-time chat interactions
- Seamless API integration
- Robust error handling
- Responsive design

## Technical Stack

- Laravel 11
- PHP 8.2+
- TailwindCSS
- Open Router API (Claude-2)

## Setup Instructions

1. Clone the repository
```bash
git clone [your-repo-url]
cd Laravel-AI-Assistant
```

2. Install dependencies
```bash
composer install
npm install
```

3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up Open Router API
- Get your API key from https://openrouter.ai/keys
- Add to your .env file:
```
OPENROUTER_API_KEY=your_api_key_here
```

5. Start the development server
```bash
php artisan serve
```

## Recent Updates (2024-03-19)

### API Integration
- Migrated to Open Router API
- Implemented Claude-2 model support
- Added flexible model selection
- Improved error handling and logging

### Frontend Enhancements
- Added cyberpunk-themed UI
- Implemented real-time chat interface
- Added typing indicators
- Improved message formatting
- Enhanced responsive design

### Controller Improvements
- Refactored AIAssistantController
- Added shared request handling
- Improved error management
- Enhanced response processing

## Usage

1. Start the development server
2. Navigate to http://localhost:8000
3. Begin chatting with the AI assistant
4. Use Shift+Enter for multi-line messages

## Security

- API keys are properly secured
- CSRF protection implemented
- Input validation in place
- Secure error handling

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the MIT license.
