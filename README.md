# pran-sqm-dev
Here’s a concise version under 350 characters:  **SQM (Social Media Queries Management)** collects customer messages and comments from social media, converts them into tickets, assigns them to agents, and tracks responses and performance, helping organizations manage interactions efficiently and improve customer support.

## AI-Powered Reply Suggestions

The application includes an AI-powered feature to help agents generate reply suggestions based on conversation history. Supports both OpenAI and Hugging Face as AI providers.

### Setup

Choose your preferred AI provider and configure the corresponding API keys in your `.env` file:

#### Option 1: OpenAI (Recommended)
```env
AI_PROVIDER=openai
OPENAI_API_KEY=your_openai_api_key_here
```

#### Option 2: Hugging Face
```env
AI_PROVIDER=huggingface
HUGGING_FACE_TOKEN=your_hugging_face_token_here
```

### Usage

When viewing a ticket conversation:
1. Click the "🤖 Replay Suggest" button next to the message textarea
2. The system will analyze the full conversation history
3. 2-3 AI-generated reply suggestions will appear below the textarea
4. Click on any suggestion to automatically fill the textarea
5. Edit the suggestion as needed before sending

### Features

- Analyzes complete conversation context
- Generates professional, context-aware responses
- Provides multiple suggestion options
- One-click selection of suggestions
- Supports both OpenAI GPT-3.5-turbo and Hugging Face GPT-2
- Fallback suggestions if AI is unavailable

## AI-Powered Conversation Summary

The application includes an AI-powered feature to automatically generate professional summaries of ticket conversations.

### Usage

When viewing a ticket in the conversations view:
1. Click the "🤖 AI" button next to the Summary section
2. The system analyzes all messages in the conversation
3. AI generates a concise 2-3 sentence summary focusing on:
   - Key customer issues and concerns
   - Main points discussed
   - Current resolution status
4. The summary is automatically populated in the Summary edit field
5. Review and edit the summary as needed
6. Click "Save" to store the summary with the ticket

### Features

- Analyzes entire conversation history automatically
- Generates concise, professional summaries
- Focuses on key issues and resolution status
- One-click generation with auto-population in edit field
- Supports both OpenAI and Hugging Face as AI providers
- Fallback error handling with helpful messages
