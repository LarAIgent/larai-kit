# Guide — SaaS Chatbot Platform Prompt

This guide explains every `[PLACEHOLDER]` in [`PROMPT.md`](PROMPT.md) and what to put there.
Fill each one in before sending the prompt to an LLM.

---

## Step 1 — Identity

| Placeholder | What to put | Example |
|---|---|---|
| `[APP_NAME]` | Your product's full display name | `MediBot AI`, `LexAI`, `ShopBot` |
| `[APP_SLUG]` | Lowercase, no spaces — used in artisan command names and .env prefixes | `medibot`, `lexai`, `shopbot` |
| `[ADMIN_EMAIL]` | Email address for the seeded admin account | `admin@medibot.ai` |
| `[ADMIN_PASSWORD]` | Password for the seeded admin account (change after first login) | `SuperSecret123!` |

---

## Step 2 — Business Context

| Placeholder | What to put | Example |
|---|---|---|
| `[TARGET_INDUSTRY]` | The sector your product serves | `Healthcare`, `Legal`, `E-commerce`, `Real Estate` |
| `[TARGET_USERS]` | Who will log in and build chatbots | `Doctors and clinic managers`, `Lawyers`, `Online shop owners` |
| `[VALUE_PROPOSITION]` | One sentence — why they'd pay for this | `AI chatbots trained on your clinic's documents and FAQs` |

These three shape the landing page copy, the hero section chat demo,
and the default system prompt placeholder text.

---

## Step 3 — Design

| Placeholder | What to put | Format |
|---|---|---|
| `[PRIMARY_COLOR]` | Your brand's main colour | 6-digit hex, e.g. `0D9488` (teal) or `6366F1` (indigo) |
| `[ACCENT_COLOR]` | Secondary / highlight colour | 6-digit hex, e.g. `3B82F6` (blue) |
| `[WARM_COLOR]` | Warning / badge colour | 6-digit hex, e.g. `EAB308` (amber) |
| `[FONT_NAME]` | Google Font name | `Inter`, `Plus Jakarta Sans`, `DM Sans`, `Nunito` |
| `[LANDING_FEEL]` | Adjectives describing the landing page vibe | `bright, medical-grade, trustworthy`, `clean, professional, corporate` |

**Colour ideas by industry:**

| Industry | Primary | Accent | Feel |
|---|---|---|---|
| Healthcare | `0D9488` (teal) | `3B82F6` (blue) | calm, trustworthy, clean |
| Legal | `1E40AF` (navy) | `6B7280` (grey) | authoritative, serious |
| E-commerce | `7C3AED` (violet) | `EC4899` (pink) | energetic, modern |
| Education | `059669` (emerald) | `F59E0B` (amber) | friendly, approachable |
| Finance | `0F172A` (slate) | `0EA5E9` (sky) | secure, premium |

---

## Step 4 — Pricing

| Placeholder | What to put | Example |
|---|---|---|
| `[PRO_PRICE]` | Monthly price for the Pro tier (number only) | `49`, `79`, `99` |

The prompt builds three pricing tiers: Free, Pro, Enterprise.
You control the Pro price here. Edit features inside the prompt if you want
different limits (default: 2 chatbots free, 10 chatbots pro).

---

## Step 5 — Integrations

| Placeholder | What to put | Example |
|---|---|---|
| `[INTEGRATION_1]` | A platform-specific integration feature name | `WhatsApp Integration`, `Telegram Bot`, `Slack App`, `Instagram DM` |

This appears in the features grid on the landing page.
Keep it to one — you can add more after generation.

---

## Step 6 — Optional Additions

These are not placeholders but sections you can add to the prompt before sending
for extra features:

### Add WhatsApp integration
Append to the FEATURES section:
```
### 15. WhatsApp Integration
- Integrations model already exists in the schema
- Add a UI under Chatbot → Integrations to enter WhatsApp Business API credentials
- Credentials stored encrypted (integrations.credentials, 'encrypted:array' cast)
- Status toggle (active/inactive)
- Webhook endpoint at POST /api/webhooks/whatsapp/{chatbot}
```

### Add billing (Stripe)
```
### 16. Billing
- Install Laravel Cashier (stripe): composer require laravel/cashier
- Plans table: id, name, slug, price_monthly, max_chatbots, max_messages, features (json)
- Gate chatbot creation behind plan limits
- Stripe checkout for plan upgrade
- Webhook at POST /webhooks/stripe
```

### Switch to PostgreSQL + pgvector
Replace the Vector Store line in the prompt with:
```
Vector store: pgvector (PostgreSQL extension)
Set LARAI_VECTOR_STORE=pgvector and DB_CONNECTION=pgsql in .env
```

### Add streaming chat
Append to the Chat API section:
```
Add streaming endpoint:
GET /api/v1/chat/{uuid}/conversations/{uuid}/stream
Returns Server-Sent Events using ChatService::streamMessage()
Widget JS listens via EventSource and appends tokens as they arrive
```

---

## Step 7 — LLM Tips

### Which model to use
| Task | Recommended |
|---|---|
| Generate the full project at once | Claude 3.7 Sonnet, GPT-4o, Gemini 1.5 Pro |
| Iterate on a single feature | Claude 3.5 Haiku, GPT-4o-mini |
| Debug a specific error | Any model — paste the error + relevant file |

### How to send the prompt
1. Copy everything inside the ``` code block in `PROMPT.md`
2. Fill every `[PLACEHOLDER]` — search for `[` to find them all
3. Paste into a new conversation (fresh context — no prior messages)
4. If the LLM stops mid-file, say: `"Continue from where you left off"`
5. If a file is incomplete, say: `"Write the complete [filename] file"`

### What to do if something breaks
The most common issues after generation:

| Error | Fix |
|---|---|
| `Class "Laraigent\LaraiKit..." not found` | Wrong namespace — correct is `LarAIgent\AiKit\...` |
| `Unable to locate component [layouts.guest]` | Move layout files to `resources/views/components/layouts/` |
| `Maximum execution time exceeded` | Add `@set_time_limit(0)` at top of ingestion/chat service methods |
| `Call to a member function toArray() on array` | larai-kit bug — update to v0.2.0+ |
| Dark mode not working | Add `@variant dark (&:where(.dark, .dark *));` to app.css |
| URL source stays Processing | Read `$asset->fresh()->ingestion->state` after ingest returns |
| PDF upload fails | Run `composer require smalot/pdfparser` |
| Scoped RAG returns nothing | Run `php artisan [app]:reindex` to backfill scope metadata |

### After generation checklist
- [ ] All `[PLACEHOLDER]` strings replaced (search for `[` in all files)
- [ ] `php artisan larai:install` run
- [ ] `php artisan migrate --force` run
- [ ] `php artisan db:seed` run (creates admin)
- [ ] `php artisan larai:doctor --deep` shows all green
- [ ] `npm run build` succeeds
- [ ] Login at `/login` with your admin credentials works
- [ ] Create a chatbot, add a text knowledge source, send a chat message
- [ ] Widget at `/widget/{uuid}` loads and responds

---

## Quick Reference — All Placeholders

```
[APP_NAME]          → Your product display name
[APP_SLUG]          → Lowercase slug (used in commands, .env keys)
[ADMIN_EMAIL]       → Seeded admin email
[ADMIN_PASSWORD]    → Seeded admin password
[TARGET_INDUSTRY]   → Industry you serve
[TARGET_USERS]      → Who your users are
[VALUE_PROPOSITION] → One-line value statement
[PRIMARY_COLOR]     → 6-digit hex, no #
[ACCENT_COLOR]      → 6-digit hex, no #
[WARM_COLOR]        → 6-digit hex, no #
[FONT_NAME]         → Google Fonts family name
[LANDING_FEEL]      → Comma-separated adjectives
[PRO_PRICE]         → Monthly price number
[INTEGRATION_1]     → Platform integration feature name
```

**Total placeholders: 14** — filling all 14 is the only thing between you and
a working enterprise chatbot SaaS.
