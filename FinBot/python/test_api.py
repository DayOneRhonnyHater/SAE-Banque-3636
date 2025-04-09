from openai import OpenAI
client = OpenAI(api_key="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")  # Remplacez par votre clé

response = client.chat.completions.create(
    model="gpt-3.5-turbo",  # Modèle de chat moderne
    messages=[
        {"role": "user", "content": "Write a one-sentence bedtime story about a unicorn."}
    ]
)

print(response.choices[0].message.content)