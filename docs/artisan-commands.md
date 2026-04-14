# Artisan Commands

## larai:install

Publish config, run migrations, and create the storage symlink.

```bash
php artisan larai:install
```

Run this once after `composer require`.

## larai:doctor

Check the health of all LarAI Kit services.

```bash
php artisan larai:doctor
```

Shows: database, AI provider, vector store, storage, cache, Redis, queue. Reports feature tier and configuration summary.

### Deep mode

Test live API calls (embedding + vector store connectivity):

```bash
php artisan larai:doctor --deep
```

This sends a real embedding request to your AI provider and validates the returned vector dimensions match your config. Catches misconfiguration in seconds.

```
LarAI Kit Health Check

  [OK]      Database (mysql) (3.2ms)
  [OK]      AI Provider (openai)
  [OK]      Embedding probe (1536 dims, 2841ms)    <-- live API test
  [OK]      Vector Store (Pinecone)
  [OK]      Storage (public)
  [OK]      Cache (file)
  [SKIP]    Redis — not configured
  [SKIP]    Queue (sync) — sync mode

Configuration:
  AI Provider:   openai
  Vector Store:  pinecone
  Database:      mysql
  Feature Tier:  2
  RAG:           enabled
```

## larai:chat

Interactive CLI chat session with the SupportAgent. Type messages, get responses. Type `exit` to quit.

```bash
php artisan larai:chat
```

## make:larai-agent

Scaffold a new Agent class.

```bash
php artisan make:larai-agent ProductAgent
```

Creates `app/Ai/Agents/ProductAgent.php`.

## make:larai-tool

Scaffold a new Tool class.

```bash
php artisan make:larai-tool CheckOrderTool
```

Creates `app/Ai/Tools/CheckOrderTool.php`.
