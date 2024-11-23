#!/bin/bash
# Create models directory
mkdir -p models

# Download CodeLlama model
wget https://huggingface.co/TheBloke/CodeLlama-7B-Instruct-GGUF/resolve/main/codellama-7b-instruct.Q4_K_M.gguf -O models/codellama-7b-instruct.Q4_K_M.gguf

# Start the service
docker-compose up -d