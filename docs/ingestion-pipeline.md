# Ingestion Pipeline

The ingestion pipeline takes raw files or text and turns them into searchable vector embeddings.

## How It Works

```
Input (file or text)
  --> Validate (type, size)
  --> Store file (local disk or S3)
  --> Parse to plain text (TextParser, PdfParser, DocxParser)
  --> Chunk with overlap (configurable size)
  --> Batch embed all chunks (OpenAI embedMany)
  --> Batch upsert to vector store (Pinecone or pgvector)
  --> Done (state: "indexed")
```

## Ingest Text

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);
$asset = $ingestion->ingestText('Your company policies and FAQ content here...');
```

### With multi-tenant scope

```php
$asset = $ingestion->ingestText(
    'Support docs for Acme Corp...',
    name: 'Acme FAQ',
    scope: ['chatbot_id' => 42],
);
```

The scope is stored on the asset and passed through the pipeline into vector metadata. When retrieving, use the same scope to isolate results per tenant.

## Ingest a File

```php
$asset = $ingestion->ingestFile($request->file('document'));

// With scope
$asset = $ingestion->ingestFile($request->file('document'), scope: ['chatbot_id' => 42]);
```

## Pipeline States

Each ingestion is tracked in the `ai_ingestions` table:

| State | Description |
|---|---|
| `queued` | Job dispatched, waiting to process |
| `parsing` | Extracting text from file |
| `chunking` | Splitting text into overlapping chunks |
| `embedding` | Generating vector embeddings (batch) |
| `indexed` | Complete — chunks are searchable |
| `failed` | Error occurred (check `error` column) |

## Pipeline Events

Three events let you hook into the pipeline. Pick the one that matches your use case:

| Event | When it fires | Timing | Use it for |
|---|---|---|---|
| `IngestionStateChanged` | Every state transition | Immediate | Progress bars, live status |
| `AssetIndexed` | State reaches `indexed` | Deferred (`afterResponse`) | Post-ingest business logic |
| `AssetFailed` | State reaches `failed` | Deferred (`afterResponse`) | Alerts, retries |

### `IngestionStateChanged` — all transitions, immediate

Fires on every state change. Good for progress tracking and logging:

```php
use LarAIgent\AiKit\Events\IngestionStateChanged;

Event::listen(IngestionStateChanged::class, function ($event) {
    Log::info("Asset {$event->asset->id}: {$event->state}", [
        'error' => $event->error,
    ]);
});
```

### `AssetIndexed` / `AssetFailed` — terminal, deferred

Fire only on the terminal states, and are dispatched via `afterResponse()` in
a web request. That means listeners run **after** your controller returns and
your outer DB transaction commits — you can safely look up the asset by the
foreign key you just stored:

```php
use LarAIgent\AiKit\Events\AssetIndexed;
use LarAIgent\AiKit\Events\AssetFailed;

Event::listen(AssetIndexed::class, function ($event) {
    // By the time this runs, the caller has committed.
    // You can safely find your domain row by ai_asset_id.
    KnowledgeBase::where('ai_asset_id', $event->asset->id)->update([
        'status' => 'indexed',
        'chunk_count' => $event->ingestion->chunk_count,
        'indexed_at' => now(),
    ]);
});

Event::listen(AssetFailed::class, function ($event) {
    Mail::to(config('alerts.ingestion'))
        ->send(new IngestionFailedMail($event->asset, $event->error));
});
```

In CLI or queue-worker contexts (no HTTP response boundary), these fire
immediately — still correct because the job has already completed.

**Prefer the terminal events when you only care about "did it work."** They
eliminate the race where a listener fires before the caller has linked the
asset ID to its own rows.

## Safety: Zero-Chunk Guard

If the pipeline reaches the "indexed" state but `chunk_count` is 0, it automatically fails instead of reporting false success. This prevents silent data loss.

## Check Ingestion Status

The returned `$asset` always has a fresh `ingestion` relationship loaded — you can read the final state directly:

```php
$asset = $ingestion->ingestText('Hello world');

// Safe — relationship is pre-loaded with final state
$asset->ingestion->state;       // 'indexed' or 'failed'
$asset->ingestion->chunk_count; // number of chunks indexed
$asset->ingestion->error;       // null or error message
```

For assets loaded from the database later:

```php
$asset = Asset::with('ingestion')->find($id);
$asset->ingestion->state;
```

## Queue Processing

By default (`QUEUE_CONNECTION=sync`), ingestion runs inline. For production:

```env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:work
```

**Important:** If using `sync` queue, large files may hit the 30-second PHP timeout. The jobs set `set_time_limit(0)` internally, but if you're behind a web server (nginx/Apache) with its own timeout, use a background queue instead.

## Batch Embedding

Chunks are embedded in batches using `EmbeddingProvider::embedMany()` instead of one-by-one. This reduces API calls by 5-10x and avoids rate-limit issues on OpenAI tier-1 keys.

## Supported File Types

| Type | Mime | Required Package |
|---|---|---|
| Plain text (.txt, .md, .csv) | text/plain, text/markdown, text/csv | Built-in |
| PDF (.pdf) | application/pdf | `composer require smalot/pdfparser` |
| Word (.docx) | application/vnd.openxmlformats-... | `composer require phpoffice/phpword` |

## Chunking Configuration

```env
LARAI_CHUNK_SIZE=512     # Words per chunk
LARAI_CHUNK_OVERLAP=50   # Overlapping words between chunks
```

## Database Tables

| Table | Purpose |
|---|---|
| `ai_documents` | Searchable documents with embeddings |
| `ai_assets` | File metadata + scope |
| `ai_chunks` | Text chunks linked to assets |
| `ai_ingestions` | Pipeline state tracking |
