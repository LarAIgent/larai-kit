# Example 01 — SaaS Chatbot Platform

**Tested on:** larai-kit v0.2.0 · Laravel 13.4 · PHP 8.4.19
**Build time:** ~30 min (LLM generation) + ~1 hour (setup & testing)
**Difficulty:** Intermediate

---

## What This Builds

A complete, multi-tenant SaaS platform where users sign up, create AI chatbots,
train them on their own documents, and embed them on any website — similar to
Chatbase or YourGPT.ai.

### Features at a glance

- User registration + login (role-based: admin / user)
- Chatbot builder with system prompt, model, temperature controls
- Knowledge base: upload files (PDF, DOCX, TXT), paste text, or enter a URL
- Embeddable chat widget (copy one `<script>` tag)
- Standalone chat page at `/widget/{uuid}`
- Conversation history with RAG source citations
- Markdown rendering in chat (bold, lists, code blocks, tables)
- Dark / light mode toggle (class-based, persisted in localStorage)
- Admin panel: manage all users and chatbots
- Multi-tenant RAG scoping — Bot A cannot see Bot B's documents
- Artisan reindex command for KB scope backfill

### Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 |
| AI toolkit | LarAI Kit v0.2.0 |
| Frontend | Blade + Tailwind CSS v4 + Vite 7 |
| Chat AI | OpenAI gpt-4o-mini |
| Embeddings | text-embedding-3-small |
| Vector store | Pinecone (serverless) |
| Markdown | marked.js + DOMPurify |
| Database | SQLite (dev) / MySQL or PostgreSQL (prod) |

---

## Files

| File | Purpose |
|---|---|
| [`PROMPT.md`](PROMPT.md) | The complete LLM prompt — copy and fill in placeholders |
| [`GUIDE.md`](GUIDE.md) | Explains every placeholder, optional add-ons, and troubleshooting |

---

## Placeholders to fill (14 total)

`[APP_NAME]` `[APP_SLUG]` `[ADMIN_EMAIL]` `[ADMIN_PASSWORD]`
`[TARGET_INDUSTRY]` `[TARGET_USERS]` `[VALUE_PROPOSITION]`
`[PRIMARY_COLOR]` `[ACCENT_COLOR]` `[WARM_COLOR]` `[FONT_NAME]` `[LANDING_FEEL]`
`[PRO_PRICE]` `[INTEGRATION_1]`

See `GUIDE.md` for what each one means and industry-specific suggestions.

---

## Result preview

```
http://your-app.test/          → Landing page (bright, industry-specific)
http://your-app.test/register  → Sign up
http://your-app.test/dashboard → Chatbot grid + stats
http://your-app.test/chatbots  → Create / manage chatbots
http://your-app.test/admin     → Admin panel (admin role only)
http://your-app.test/widget/{uuid} → Embeddable chat page
```
