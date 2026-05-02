# Redundancy Audit - v0.2.4

This report lists non-blocking cleanup/debt found while fixing issues #9-#13.
Items below are intentionally not changed in this release.

## Findings

1. `DeleteAssetVectorsJob::$assetId` is currently unused in `handle()`.
- Status: harmless but redundant field.
- Follow-up: either remove the field in a backward-compatible cycle or use it for logging/metrics.

2. URL source-name extraction exists in two places.
- Locations: `IngestionService::extractNameFromUrl()` and `FetchUrlAssetJob::extractNameFromUrl()`.
- Status: small duplication introduced to keep job self-contained.
- Follow-up: extract to a shared helper/value object in a future refactor.

3. MIME fallback mapping is embedded in `IngestionService`.
- Status: valid for now, but parser capability and extension mapping are split across components.
- Follow-up: centralize into parser registry metadata to reduce drift risk.

4. README/changelog contain legacy mojibake characters from earlier edits.
- Status: documentation-only quality issue.
- Follow-up: run a targeted docs encoding cleanup pass and normalize punctuation.

5. Feature tests run `migrate:fresh` per class.
- Status: clear and stable, but slower as suite grows.
- Follow-up: introduce shared DB refresh trait/strategy to reduce repetition and runtime.
