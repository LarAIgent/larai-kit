# Prompt — SaaS Chatbot Platform (Laravel 13 + LarAI Kit)

> Copy the prompt below into any capable LLM (Claude, GPT-4o, Gemini).
> Fill in every `[PLACEHOLDER]` before sending.
> See [`GUIDE.md`](GUIDE.md) for what each placeholder means and optional add-ons.
> See [`README.md`](README.md) for a feature overview and result preview.

---

## THE PROMPT

```
You are an expert Laravel 13 engineer. Build me a complete, enterprise-grade,
multi-tenant SaaS chatbot platform called [APP_NAME] from scratch using the
stack and specifications below. Write all code — do not skip files, do not use
stubs, do not say "you can add this later". Every feature listed must be
implemented and working.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STACK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- PHP 8.4 / Laravel 13
- LarAI Kit v0.2.1 (package: laraigent/larai-kit) — provides RAG, ingestion
  pipeline, vector search, OpenAI embeddings, Pinecone/pgvector support
- Tailwind CSS v4 (class-based dark mode via `@variant dark (&:where(.dark, .dark *))`)
- Vite 7 for assets; vanilla JS (no React/Vue)
- marked.js + DOMPurify for Markdown rendering in chat
- SQLite for development, MySQL/PostgreSQL ready for production
- QUEUE_CONNECTION=sync for development (document how to switch to database queue)
- AI provider: OpenAI (gpt-4o-mini for chat, text-embedding-3-small for embeddings)
- Vector store: Pinecone (serverless)
- smalot/pdfparser for PDF ingestion

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BUSINESS CONTEXT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Product name    : [APP_NAME]
Target industry : [TARGET_INDUSTRY]       e.g. Healthcare / Legal / E-commerce
Target users    : [TARGET_USERS]          e.g. Doctors, Lawyers, Shop owners
Core value prop : [VALUE_PROPOSITION]     e.g. AI chatbots that know your medical docs
Admin email     : [ADMIN_EMAIL]
Admin password  : [ADMIN_PASSWORD]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DESIGN SYSTEM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Primary color   : [PRIMARY_COLOR]         e.g. #0D9488 (teal)
Accent color    : [ACCENT_COLOR]          e.g. #3B82F6 (blue)
Warm color      : [WARM_COLOR]            e.g. #EAB308 (amber)
Google Font     : [FONT_NAME]             e.g. Inter
Landing feel    : [LANDING_FEEL]          e.g. bright, medical-grade, trustworthy

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURES TO BUILD
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### 1. Database Schema

Users table — add these columns to the default:
  role (default: 'user'), avatar (nullable), company (nullable),
  phone (nullable), is_active (default: true)

Chatbots table:
  id, uuid (unique), user_id (FK), name, description, model (default: gpt-4o-mini),
  system_prompt, welcome_message, avatar, primary_color (#[PRIMARY_COLOR]),
  status (active/inactive), temperature (0.7), max_tokens (1024),
  rag_enabled (bool), allowed_domains (json), settings (json),
  total_conversations, total_messages, timestamps, softDeletes

knowledge_bases table:
  id, chatbot_id (FK), ai_asset_id (nullable — links to larai-kit Asset),
  name, description, source_type (file/text/url), source_name,
  file_path, mime_type, file_size, status (pending/processing/indexed/failed),
  error_message, chunk_count, indexed_at, timestamps

conversations table:
  id, uuid (unique), chatbot_id (FK), visitor_id, visitor_name, visitor_email,
  channel (widget/api/whatsapp), status (active/closed), metadata (json),
  last_message_at, timestamps

messages table:
  id, conversation_id (FK), role (user/assistant/system), content (text),
  sources (json — RAG citations), token_count, timestamps

widget_configurations table:
  id, chatbot_id (FK, unique), position (bottom-right/bottom-left), theme,
  primary_color, text_color, bubble_icon, header_title, placeholder_text,
  show_branding, collect_email, collect_name, custom_css (json), timestamps

chatbot_api_tokens table:
  id, chatbot_id (FK), name, token (64 chars, unique, hidden), last_used_at,
  expires_at, timestamps

integrations table:
  id, chatbot_id (FK), platform, status, credentials (encrypted json),
  settings (json), timestamps. Unique on (chatbot_id, platform).

### 2. Authentication

- Login, Register, Forgot Password, Reset Password — custom controllers
  (no Breeze/Jetstream)
- Role system: 'admin' and 'user'
- AdminMiddleware (abort 403 if not admin)
- EnsureUserIsActive middleware (logout inactive users with message)
- Register in bootstrap/app.php with aliases 'admin' and 'active'
- Admin seeder: reads HEIDI_ADMIN_EMAIL / HEIDI_ADMIN_PASSWORD from .env

### 3. Landing Page

- Fixed nav: logo, nav links (Features, How It Works, Pricing), auth buttons
- Hero section: gradient background with blurred color orbs, headline,
  subheadline for [TARGET_INDUSTRY], CTA buttons, animated chat mockup demo
  showing a realistic [TARGET_INDUSTRY] conversation
- Features grid (6 cards): Knowledge Base RAG, Embeddable Widget,
  [INTEGRATION_1], Enterprise Security, AI Models, Analytics
- How It Works: 3-step numbered process
- Pricing: 3 tiers (Free / Pro at [PRO_PRICE]/mo / Enterprise custom)
- CTA section: gradient brand background, headline, button
- Footer: logo, copyright

Color palette: [PRIMARY_COLOR] as primary teal, [ACCENT_COLOR] as blue accent.
The page must feel [LANDING_FEEL] — chosen for [TARGET_USERS].

### 4. App Layout (post-login)

Sidebar navigation:
- Logo (colored icon + app name gradient text)
- Nav items: Dashboard, Chatbots, Profile
- Admin section (only visible to admins): Admin Panel
- Bottom user card: avatar initial, name, email, logout button

Top bar:
- Hamburger (mobile), page title slot
- Dark mode toggle (moon/sun icon)
  IMPORTANT: use `@variant dark (&:where(.dark, .dark *))` in app.css
  so Tailwind v4 uses class-based dark mode, not prefers-color-scheme.
  JS: toggle `dark` class on `<html>`, persist in localStorage as 'app-theme'.

Flash messages: green for success, red for error — with icons.

### 5. Dashboard

Stats row (4 cards): Total Chatbots, Conversations, Active Bots, Total Messages
Chatbots grid: card per chatbot showing colored avatar, name, description,
status badge, conversation count, knowledge source count.
Empty state with CTA to create first chatbot.
"New Chatbot" button top-right.

### 6. Chatbot Management

Create form fields:
  Name, Description, AI Model (dropdown: gpt-4o-mini / gpt-4o / gpt-4-turbo),
  System Prompt (textarea, monospace), Welcome Message, Temperature, Max Tokens

Show page tabs/sections:
  - Overview stats (conversations, messages, KB sources)
  - System prompt display
  - Embed code snippet (copy-ready <script> tag)
  - Direct chat link
  - Configuration sidebar (model, temperature, max_tokens, rag status, created date)
  - Knowledge base mini-list with status dots
  - Danger zone (delete with confirmation)

Edit form: same fields as create + Status (active/inactive)

### 7. Knowledge Base Management

Index table columns: Name, Type, Status (colored badge + error message on fail),
Chunks, Actions (Remove).

Create form with 3 source types (radio/card selector):
  - File upload (.txt, .pdf, .docx, .csv, .md — max 20MB)
  - Plain text (textarea)
  - URL (input)

CRITICAL IMPLEMENTATION NOTES:
a) Use larai-kit's IngestionService directly:
   `$asset = $this->ingestion->ingestFile($file, ['chatbot_id' => $chatbot->id])`
   `$asset = $this->ingestion->ingestText($content, $name, ['chatbot_id' => $chatbot->id])`
   `$asset = $this->ingestion->ingestUrl($url, ['chatbot_id' => $chatbot->id])`
   The scope array is REQUIRED for multi-tenant isolation.

b) After each ingest call, read the final state directly from the returned asset
   because the IngestionStateChanged event fires DURING the call (before you can
   set ai_asset_id on your KB row) — a known package race condition:
   ```php
   $ingestion = $asset->fresh()->ingestion;
   $kb->update([
       'ai_asset_id'   => $asset->id,
       'status'        => $ingestion?->state === 'indexed' ? 'indexed' : ($ingestion?->state === 'failed' ? 'failed' : 'processing'),
       'chunk_count'   => $ingestion?->chunk_count ?? 0,
       'error_message' => $ingestion?->error,
       'indexed_at'    => $ingestion?->state === 'indexed' ? now() : null,
   ]);
   ```

c) Add `set_time_limit(0)` at the top of the ingestion service call —
   embedding + Pinecone upserts exceed the default 30s PHP limit for
   any non-trivial document.

d) Register an IngestionStateChanged listener for async queue support:
   ```php
   Event::listen(IngestionStateChanged::class, SyncKnowledgeBaseStatus::class);
   ```
   The listener must look up KB by BOTH ai_asset_id AND source_name as fallback.

e) Install smalot/pdfparser for PDF support:
   `composer require smalot/pdfparser`

### 8. Chat Widget

Embeddable page at GET /widget/{chatbot} (public, no auth):
- Full-page responsive chat UI
- Custom colors from WidgetConfiguration
- Online status indicator
- Markdown rendering for assistant messages (marked.js + DOMPurify via CDN)
- Typing indicator (animated dots)
- RAG source chips shown below each sourced reply
- Timestamps on every message
- XSS-safe: all AI output through DOMPurify.sanitize()
- Auto-scroll to latest message
- Button disabled + input disabled during AI response

### 9. Widget Settings

- Color pickers (primary + text color) with live hex display
- Position dropdown (bottom-right / bottom-left)
- Theme dropdown (light / dark / auto)
- Placeholder text
- Checkboxes: Show Branding, Collect Email, Collect Name
- Embed code display + direct link with copy button

### 10. Conversations

Index: list with visitor name/email, channel badge, message count, status badge,
relative timestamp. Click to view full thread.

Show: split layout — messages on left (markdown-rendered for assistant),
visitor details panel on right (name, email, channel, status, started time,
message count). RAG source badges on each sourced reply.

### 11. Chat API

Routes (prefix /api/v1/chat, throttle 60/min):
  POST /{uuid}/conversations            — start conversation
  POST /{uuid}/conversations/{id}/messages — send message, get AI reply
  GET  /{uuid}/conversations/{id}/messages — fetch history

CRITICAL IMPLEMENTATION:
- Use a custom ChatbotChatService (thin wrapper around larai-kit ChatService)
  that injects per-chatbot system_prompt, model, conversation history, AND scope.
- Always pass scope: ['chatbot_id' => $chatbot->id] to scoped RAG retrieval.
- Check if chatbot has scoped vectors (ai_assets.scope is not null) — if yes use
  scope, otherwise fall back to unscoped (for backwards compat).
- Add `set_time_limit(0)` at the top of the send method.
- Store messages in your messages table with sources JSON.
- Increment chatbot.total_messages after each exchange.

ChatbotChatService pattern:
```php
$agent = new AnonymousAgent(
    instructions: $chatbot->system_prompt ?: 'You are a helpful assistant.',
    messages: [],
    tools: [],
);
$response = $this->chat->sendMessage(
    message: $message,
    agent: $agent,
    history: $this->buildHistory($conversation),
    scope: ['chatbot_id' => $chatbot->id],
);
```

### 12. Profile

Edit form: Name, Email, Company, Phone
Password change form: Current Password, New Password, Confirm Password
Both as separate form sections on same page.

### 13. Admin Panel

Dashboard: stats (total users, chatbots, conversations, messages, new users today,
active chatbots), recent users table, recent chatbots table.

Users management: searchable table with role badge, chatbot count, active/inactive
status badge, activate/deactivate toggle (cannot deactivate admins).

Chatbots management: searchable table with owner name, model, conversation count,
KB count, status badge.

All admin routes behind 'admin' middleware.

### 14. Artisan Command

`php artisan [APP_SLUG]:reindex`
Re-ingests all KB entries with proper chatbot scope. Useful after upgrading
larai-kit. Flags: --chatbot=UUID, --force.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PACKAGE QUIRKS — READ BEFORE WRITING ANY CODE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

These are production-tested gotchas with larai-kit v0.2.0:

1. NAMESPACE: always use `LarAIgent\AiKit\...` — NOT `Laraigent\LaraiKit\...`
   (case-sensitive on Linux; wrong namespace is the #1 new-user mistake)

2. SCOPE IS MANDATORY for SaaS: pass `scope: ['chatbot_id' => $id]` to
   ingestFile/Text/Url AND to ChatService::sendMessage. Without it, every
   chatbot searches the ENTIRE Pinecone index — customers see each other's data.

3. STALE INGESTION STATE: the Asset returned by ingestText/File/Url has a
   pre-hydrated ingestion relationship. Always call `$asset->fresh()->ingestion`
   to get the real final state after sync-queue processing.

4. SET TIME LIMIT: add `@set_time_limit(0)` in any method that calls
   the embedder or Pinecone — default 30s kills multi-chunk documents.

5. PDF PARSER: requires `composer require smalot/pdfparser`.
   larai:doctor does not warn about missing optional parsers.

6. DARK MODE: Tailwind v4 defaults to media-query dark mode.
   Add `@variant dark (&:where(.dark, .dark *));` to app.css BEFORE
   the @theme block. JS must toggle `dark` class on <html> element.

7. MARKDOWN: AI replies contain markdown. Use marked.js + DOMPurify to
   render them. Never set innerHTML without sanitizing AI output.

8. UPGRADE MIGRATION: larai-kit v0.2.0 added `scope` column to ai_assets
   by modifying the original migration — existing installs need a manual
   migration to add the column. Always check for this after composer update.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
.ENV VARIABLES TO CONFIGURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

APP_NAME="[APP_NAME]"
OPENAI_API_KEY=
PINECONE_API_KEY=
PINECONE_INDEX_HOST=
LARAI_AI_PROVIDER=openai
LARAI_CHAT_MODEL=gpt-4o-mini
LARAI_EMBEDDING_MODEL=text-embedding-3-small
LARAI_VECTOR_STORE=pinecone
LARAI_EMBEDDING_DIMENSIONS=1536
LARAI_SIMILARITY_THRESHOLD=0.4
LARAI_RAG_TOP_K=5
LARAI_CHUNK_SIZE=512
LARAI_CHUNK_OVERLAP=50
LARAI_MAX_FILE_MB=20
LARAI_STORAGE_DISK=public
LARAI_HISTORY_TURNS=10
LARAI_RETRY_MAX=5
LARAI_RETRY_DELAY_MS=1000
[APP_SLUG]_ADMIN_NAME="Admin"
[APP_SLUG]_ADMIN_EMAIL=[ADMIN_EMAIL]
[APP_SLUG]_ADMIN_PASSWORD=[ADMIN_PASSWORD]
QUEUE_CONNECTION=sync

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ROUTES STRUCTURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

web.php:
  GET  /                                   → welcome view (landing)
  Guest middleware group:
    GET/POST /login, /register
    GET/POST /forgot-password, /reset-password/{token}
  Auth middleware group:
    POST /logout
    GET  /dashboard
    GET/PUT /profile, /profile/password
    Resource: /chatbots (ChatbotController)
    Resource: /chatbots/{chatbot}/knowledge-bases (only index/create/store/destroy)
    GET /chatbots/{chatbot}/conversations
    GET /chatbots/{chatbot}/conversations/{uuid}
    GET/PUT /chatbots/{chatbot}/widget
    Admin middleware group (prefix /admin):
      GET /admin, /admin/users, /admin/chatbots
      PATCH /admin/users/{user}/toggle
  Public:
    GET /widget/{chatbot}    → standalone embed page

api.php:
  Throttle 60/min:
    POST /v1/chat/{uuid}/conversations
    POST /v1/chat/{uuid}/conversations/{uuid}/messages
    GET  /v1/chat/{uuid}/conversations/{uuid}/messages

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FILE / FOLDER STRUCTURE TO CREATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

app/
  Console/Commands/ReindexKnowledgeBases.php
  Http/
    Controllers/
      Auth/LoginController.php
      Auth/RegisterController.php
      Auth/ForgotPasswordController.php
      Auth/ResetPasswordController.php
      Admin/AdminDashboardController.php
      ChatApiController.php
      ChatbotController.php
      ConversationController.php
      DashboardController.php
      KnowledgeBaseController.php
      ProfileController.php
      WidgetController.php
    Middleware/
      AdminMiddleware.php
      EnsureUserIsActive.php
  Listeners/
    SyncKnowledgeBaseStatus.php
  Models/
    Chatbot.php, ChatbotApiToken.php, Conversation.php
    Integration.php, KnowledgeBase.php, Message.php
    User.php, WidgetConfiguration.php
  Providers/
    AppServiceProvider.php  ← register IngestionStateChanged listener
  Services/
    ChatbotChatService.php

resources/
  css/app.css              ← @variant dark (&:where(.dark, .dark *)) first
  js/app.js                ← import marked + DOMPurify, window.renderMarkdown
  views/
    layouts/
      app.blade.php        ← sidebar + dark mode toggle + flash messages
    components/layouts/
      guest.blade.php      ← centered card layout for auth pages
    admin/
      dashboard.blade.php, users.blade.php, chatbots.blade.php
    auth/
      login.blade.php, register.blade.php
      forgot-password.blade.php, reset-password.blade.php
    chatbots/
      index.blade.php, create.blade.php, show.blade.php, edit.blade.php
    conversations/
      index.blade.php, show.blade.php
    knowledge-bases/
      index.blade.php, create.blade.php
    widgets/
      edit.blade.php, embed.blade.php
    dashboard.blade.php, welcome.blade.php
    profile/edit.blade.php

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COMMANDS TO RUN AFTER GENERATING CODE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

composer require laraigent/larai-kit:^0.2.0 smalot/pdfparser
npm install marked dompurify
php artisan larai:install
php artisan migrate --force
php artisan db:seed
php artisan larai:doctor --deep
npm run build

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
QUALITY STANDARDS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Every controller method must have proper authorization (abort 403 if user
  doesn't own the chatbot)
- Credentials (integrations table) must use Laravel's `encrypted` cast
- All forms must have @csrf; DELETE actions must use @method('DELETE')
- Dark mode: every view must have dark: Tailwind variants on bg, text, border
- No inline scripts with unescaped user data — use e() or @js() everywhere
- Chatbot UUID (not numeric ID) in all public-facing URLs
- Rate limiting on API routes
- SoftDeletes on chatbots
- No hardcoded colours — use CSS variables / Tailwind theme tokens
- Markdown from AI must always go through DOMPurify before innerHTML
```

---

*Generated from a real production build of a healthcare SaaS chatbot platform.*
*Stack: Laravel 13.4 · PHP 8.4.19 · larai-kit v0.2.0 · Pinecone serverless · OpenAI GPT-4o-mini*
