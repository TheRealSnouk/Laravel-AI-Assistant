import requests
import sys

def get_completion(prompt):
    response = requests.post(
        "http://localhost:8080/v1/chat/completions",
        json={
            "model": "codellama",
            "messages": [{"role": "user", "content": prompt}],
            "temperature": 0.7
        }
    )
    return response.json()["choices"][0]["message"]["content"]

if __name__ == "__main__":
    prompt = " ".join(sys.argv[1:])
    if prompt:
        print(get_completion(prompt))
    else:
        print("Please provide a prompt")