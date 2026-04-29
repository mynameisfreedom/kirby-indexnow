# Changelog

All notable changes to the IndexNow for Kirby plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-29

### Added
- Initial release
- Automatic URL submission to IndexNow on page create, update, publish, slug/URL/title changes
- Configurable endpoint with protocol-neutral default reaching all participating search engines
- Batch submission support to avoid duplicate POSTs within a single request
- Comprehensive logging to `site/logs/indexnow.log` with debug mode
- Panel sidebar view displaying status, configured endpoint, and last 500 log lines
- Panel actions: reload, write test entry, clear log
- Debug-only test route `/indexnow-test` for verification without publishing content
- CSRF protection for Panel API endpoints
- Smart deduplication and URL normalization
- Draft detection (only published pages submitted)
- File asset exclusion (no asset URLs submitted)
- Configurable batching limits (`maxPerBatch`)
- Optional hook debug tracing (`indexnow.hookDebug`)

### Configuration Options
- `indexnow.enabled` - Enable/disable submissions (default: `!debug`)
- `indexnow.endpoint` - IndexNow API endpoint (default: `https://api.indexnow.org/indexnow`)
- `indexnow.key` - Your IndexNow key
- `indexnow.keyFile` - Key filename at web root
- `indexnow.log` - Force logging in production (default: `false`)
- `indexnow.hookDebug` - Log hook traces without submitting (default: `false`)
- `indexnow.maxPerBatch` - Max URLs per shutdown batch (default: `1000`)
