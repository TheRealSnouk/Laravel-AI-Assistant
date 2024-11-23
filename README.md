# Laravel AI Assistant

A self-hosted AI coding assistant specialized in Laravel development, powered by CodeLlama and LocalAI.

## 🚀 Features

- Laravel-specific code generation
- Runs completely locally - no cloud dependencies
- Supports modern PHP 8+ and Laravel best practices
- Generates controllers, models, migrations, and more
- Follows PSR-12 coding standards

## 🖥️ Requirements

- NVIDIA GPU with at least 8GB VRAM (recommended)
- Docker and Docker Compose
- PHP 8.1 or higher
- Composer
- 8GB RAM minimum
- 20GB free disk space

## 🛠️ Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/laravel-ai-assistant
cd laravel-ai-assistant
```

2. Install PHP dependencies:
```bash
composer install
```

3. Make scripts executable:
```bash
chmod +x setup.sh laravel-assistant.php
```

4. Run the setup script (downloads model and starts the service):
```bash
./setup.sh
```

## 💻 Usage

Generate Laravel code using natural language prompts:

```bash
php laravel-assistant.php "Create a User model with email verification"
```

## 📝 Example Prompts

Check `example-prompts.md` for more usage examples, including:
- Model generation
- Controller creation
- Database migrations
- API resources
- Form requests
- Service classes
- Unit tests

## 🔧 Configuration

- Edit `config.yaml` to adjust model parameters
- Modify `docker-compose.yml` for container settings
- Update prompts in `config.yaml` for different coding styles

## 📚 Project Structure

```
.
├── config.yaml           # LocalAI and model configuration
├── docker-compose.yml    # Docker services configuration
├── setup.sh             # Installation script
├── laravel-assistant.php # PHP client for the AI service
├── composer.json        # PHP dependencies
├── models/              # AI model storage
└── example-prompts.md   # Usage examples
```

## ⚙️ Advanced Configuration

### Adjusting Model Parameters

In `config.yaml`:
- `context_size`: Adjust for longer code generation (default: 4096)
- `threads`: CPU threads to use (default: 4)
- `gpu_layers`: GPU acceleration layers (default: 35)

### Custom Prompts

Modify the prompt templates in `config.yaml` to:
- Change coding style
- Add specific conventions
- Include custom documentation formats

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

MIT License - feel free to use and modify for your needs.

## ⚠️ Disclaimer

This is a self-hosted solution using the CodeLlama model. Ensure you comply with the model's license terms and your local regulations regarding AI usage.