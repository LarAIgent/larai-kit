# LarAI Kit — Community Examples & Prompts

Real-world starter prompts built and tested by the community on top of LarAI Kit.
Each example is a complete, copy-paste prompt for an LLM that generates a working
Laravel 13 + LarAI Kit application from scratch.

---

## Available Prompts

| # | Example | Industry | What it builds |
|---|---|---|---|
| [01](prompts/01-saas-chatbot-platform/) | SaaS Chatbot Platform | Any | Multi-tenant platform where users build & embed their own AI chatbots |

*More coming — see [Contributing](#contributing).*

---

## How to Use

1. Open the example folder you want
2. Read the `GUIDE.md` to understand and fill in placeholders
3. Copy the prompt from `PROMPT.md`
4. Paste into Claude, GPT-4o, or Gemini
5. Follow the post-generation checklist in the guide

---

## Prompt Categories (planned)

| Category | Description |
|---|---|
| **SaaS platforms** | Multi-tenant products built on top of LarAI Kit |
| **Add-to-existing** | Inject AI chatbot into an already-built Laravel app |
| **Industry-specific** | E-commerce, Legal, Healthcare, CRM — pre-tuned prompts |
| **Integration-first** | WhatsApp-only, Slack-only, API-only chatbots |

---

## Contributing

Have you built something with LarAI Kit? Add your prompt as an example.

### Folder structure for a new example

```
examples/
  prompts/
    NN-your-example-name/        ← next sequential number + kebab-case name
      PROMPT.md                  ← the LLM prompt (required)
      GUIDE.md                   ← placeholder guide + tips (required)
      README.md                  ← 1-page summary with preview (optional)
```

### Content standards

- Every `[PLACEHOLDER]` must be documented in `GUIDE.md`
- Include any package quirks or gotchas discovered during the build
- Include a post-generation checklist in `GUIDE.md`
- Mark which larai-kit version the prompt was tested against

### Open a PR

Title format: `examples: add [what it builds] prompt`

Example: `examples: add SaaS chatbot platform prompt`
