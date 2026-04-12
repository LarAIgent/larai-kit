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

Shows status of: database, AI provider, vector store, storage, cache, Redis, queue. Reports the current feature tier and configuration.

## larai:chat

Interactive CLI chat session with the SupportAgent. Useful for testing your RAG pipeline without a web UI.

```bash
php artisan larai:chat
```

Type messages and get responses. Type `exit` to quit.

## make:larai-agent

Scaffold a new Agent class.

```bash
php artisan make:larai-agent ProductAgent
```

Creates `app/Ai/Agents/ProductAgent.php` implementing `Agent` and `HasTools`.

## make:larai-tool

Scaffold a new Tool class.

```bash
php artisan make:larai-tool CheckOrderTool
```

Creates `app/Ai/Tools/CheckOrderTool.php` implementing `Tool` with `description()`, `handle()`, and `schema()` methods.
