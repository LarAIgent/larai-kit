# Ingestion Pipeline

The ingestion pipeline converts files, text, and URLs into searchable vectors.

## Entry Points

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);

// Upload from request
$asset = $ingestion->ingestFile($request->file('document'), scope: ['chatbot_id' => 42]);

// Re-ingest file already stored on a Laravel disk
$asset = $ingestion->ingestFromDisk('public', 'knowledge/report.pdf', scope: ['chatbot_id' => 42]);

// Raw text
$asset = $ingestion->ingestText('Company policy text...', name: 'Policy', scope: ['chatbot_id' => 42]);

// URL (safety checks run immediately; fetch/parse/chunk runs in jobs)
$asset = $ingestion->ingestUrl('https://example.com/docs/faq', scope: ['chatbot_id' => 42]);
```

## Processing Flow

All entry points now use queued jobs for heavy work:

```text
queued -> parsing -> chunking -> embedding -> indexed
                                 \-> failed
```

Jobs involved:

- `ParseAssetJob` for uploaded/disk files
- `ProcessTextAssetJob` for raw text ingestion
- `FetchUrlAssetJob` for URL fetch + content extraction
- `ChunkAssetJob` for chunk creation
- `EmbedChunksJob` for embedding + vector upsert

## States

| State | Meaning |
|---|---|
| `queued` | Accepted and waiting for queued processing |
| `parsing` | Parsing/extraction stage (including URL fetch) |
| `chunking` | Chunk creation in progress |
| `embedding` | Embeddings + vector upsert in progress |
| `indexed` | Successfully indexed and searchable |
| `failed` | Terminal failure (`error` column contains details) |

## Events

| Event | Fires When | Timing |
|---|---|---|
| `IngestionStateChanged` | Every state transition | Immediate |
| `AssetIndexed` | Terminal success | After commit when inside transaction |
| `AssetFailed` | Terminal failure | After commit when inside transaction |

Use terminal events for business workflows that depend on committed domain writes:

```php
use Illuminate\Support\Facades\Event;
use LarAIgent\AiKit\Events\AssetIndexed;

Event::listen(AssetIndexed::class, function ($event) {
    KnowledgeBase::where('ai_asset_id', $event->asset->id)->update([
        'status' => 'indexed',
        'chunk_count' => $event->ingestion->chunk_count,
        'indexed_at' => now(),
    ]);
});
```

## Queue Notes

- `QUEUE_CONNECTION=sync` still executes inline (Laravel default behavior).
- For true async offload in production, use `database`, `redis`, etc. and run workers.

```env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:work
```

## Supported File Types

| Type | MIME | Parser |
|---|---|---|
| Text | `text/plain`, `text/markdown`, `text/csv` | Built-in |
| HTML | `text/html`, `application/xhtml+xml` | Built-in |
| PDF | `application/pdf` | `smalot/pdfparser` |
| DOCX | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` | `phpoffice/phpword` |
