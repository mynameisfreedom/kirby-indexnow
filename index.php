<?php

// Minimal IndexNow submitter for Kirby

if (!function_exists('indexnow_log')) {
    /**
     * Lightweight logger to site/logs/indexnow.log when debug or indexnow.log is enabled
     */
    function indexnow_log(string $message): void
    {
        try {
            $kirby = kirby();
            $shouldLog = (bool)($kirby->option('indexnow.log') ?? false) || (bool)($kirby->option('debug') ?? false);
            if (!$shouldLog) {
                return;
            }
            $logsDir = $kirby->root('logs');
            // Ensure logs directory exists
            if (!is_dir($logsDir)) {
                @mkdir($logsDir, 0775, true);
            }
            $logFile = $logsDir . DIRECTORY_SEPARATOR . 'indexnow.log';
            $line = date('c') . ' ' . $message . PHP_EOL;
            $ok = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
            if ($ok === false) {
                // Surface failure to PHP error log to aid diagnosis in production
                @error_log('[indexnow] failed to write log at ' . $logFile . ' :: ' . $message);
            }
        } catch (\Throwable $e) {
            // Fallback to PHP error log if anything goes wrong
            @error_log('[indexnow] ' . $message);
        }
    }
}

if (!function_exists('indexnow_submit')) {
    /**
     * Submit a set of URLs to IndexNow
     * @param array $urls List of absolute URLs
     * @param array $opts Optional overrides: enabled, endpoint, key, keyFile
     * @return int|null HTTP status code on cURL attempt, or null when not available
     */
    function indexnow_submit(array $urls, array $opts = []): ?int
    {
        $kirby = kirby();

        $enabled  = $opts['enabled']  ?? ($kirby->option('indexnow.enabled') ?? ($kirby->option('debug') !== true));
        $endpoint = $opts['endpoint'] ?? ($kirby->option('indexnow.endpoint') ?? 'https://api.indexnow.org/indexnow');
        $key      = $opts['key']      ?? $kirby->option('indexnow.key');
        $keyFile  = $opts['keyFile']  ?? $kirby->option('indexnow.keyFile');

        if (!$enabled || empty($key) || empty($keyFile) || empty($urls)) {
            indexnow_log('Skipped submission (enabled/key/keyFile/urls not satisfied).');
            return null;
        }

        $baseUrl = site()->url();
        $parts   = parse_url($baseUrl);
        $scheme  = $parts['scheme'] ?? 'https';
        $host    = $parts['host']   ?? '';

        if ($host === '') {
            indexnow_log('Skipped submission (host could not be determined from site()->url()).');
            return null;
        }

        $keyLocation = $scheme . '://' . $host . '/' . ltrim($keyFile, '/');

        $payload = json_encode([
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $keyLocation,
            'urlList'     => array_values(array_unique($urls)),
        ], JSON_UNESCAPED_SLASHES);

        // Try cURL first, fallback to file_get_contents
        $ok = false;
        $code = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $ok = ($code >= 200 && $code < 300);
            indexnow_log('Submitted via cURL with HTTP ' . (string)$code . ' for ' . implode(', ', $urls));
        }

        if (!$ok) {
            @file_get_contents($endpoint, false, stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => 5,
                ],
            ]));
            indexnow_log('Submitted via stream wrapper (status unknown) for ' . implode(', ', $urls));
        }

        return $code;
    }
}

// In-request batching to avoid multiple POSTs for rapid successive events
if (!function_exists('indexnow_enqueue')) {
    /**
     * Queue URLs for a single batched submission at shutdown
     */
    function indexnow_enqueue(array $urls): void
    {
        static $queue = [];
        static $registered = false;

        // Normalize and merge
        foreach ($urls as $u) {
            if (is_string($u) && $u !== '') {
                $queue[$u] = true; // dedupe via keys
            }
        }

        if (!$registered) {
            $registered = true;
            register_shutdown_function(function () use (&$queue) {
                if (empty($queue)) {
                    return;
                }
                $max = kirby()->option('indexnow.maxPerBatch') ?? 1000;
                $batch = array_slice(array_keys($queue), 0, (int)$max);
                indexnow_submit($batch);
                // leave remainder (if any) untouched; next request can pick up fresh queue only
                $queue = [];
            });
        }
    }
}

if (!function_exists('indexnow_handle_page_direct')) {
    /**
     * Handle page submission - accepts page object directly from Kirby hooks
     * 
     * Note: Kirby :after hooks pass data as direct function parameters, not event objects.
     * For page hooks, the signature is: function($newPage, $oldPage)
     * 
     * @param mixed $page The page object from the hook
     */
    function indexnow_handle_page_direct($page): void
    {
        if (!$page || !is_object($page) || !method_exists($page, 'url')) {
            return;
        }

        // Skip drafts; only submit for published pages (listed/unlisted)
        if (method_exists($page, 'isDraft') && $page->isDraft()) {
            return;
        }
        
        $hookDebug = (bool)(kirby()->option('indexnow.hookDebug') ?? false);
        if ($hookDebug) {
            indexnow_log('Hook accepted: url=' . $page->url());
        }
        
        indexnow_enqueue([$page->url()]);
    }
}



// Read the last $lines of the log file (used by the Panel area)
if (!function_exists('indexnow_tail')) {
    function indexnow_tail(string $path, int $lines = 200): array
    {
        if (!is_file($path)) {
            return [];
        }
        $content = @file($path, FILE_IGNORE_NEW_LINES);
        if ($content === false) {
            return [];
        }
        return array_slice($content, -1 * max(1, $lines));
    }
}

if (!function_exists('indexnow_panel_props')) {
    /**
     * Build props for the custom Panel IndexNow view
     */
    function indexnow_panel_props(int $lineCount = 500): array
    {
        $kirby = kirby();
        $logPath = $kirby->root('logs') . DIRECTORY_SEPARATOR . 'indexnow.log';

        return [
            'enabled'  => (bool)($kirby->option('indexnow.enabled') ?? ($kirby->option('debug') !== true)),
            'endpoint' => (string)($kirby->option('indexnow.endpoint') ?? 'https://api.indexnow.org/indexnow'),
            'lines'    => indexnow_tail($logPath, $lineCount),
            'logRoute' => url('indexnow-api'),
            'csrf'     => csrf(),
        ];
    }
}

// Build hooks array with optional debug tracer only when enabled
$indexnowHooks = [
    // IMPORTANT: Kirby :after hooks receive page objects as direct function parameters
    // The signature is: function($newPage, $oldPage) { ... }
    // NOT: function($event) { $page = $event->page(); }
    'page.create:after'       => function ($newPage) { indexnow_handle_page_direct($newPage); },
    'page.update:after'       => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    'page.changeStatus:after' => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    'page.publish:after'      => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    'page.changeSlug:after'   => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    'page.changeUrl:after'    => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    'page.changeTitle:after'  => function ($newPage, $oldPage) { indexnow_handle_page_direct($newPage); },
    // Intentionally no file.* hooks to avoid submitting asset URLs
];

if (kirby()->option('indexnow.hookDebug')) {
    $indexnowHooks['page.*:after'] = function ($event) {
        $name   = method_exists($event, 'name') ? $event->name() : 'page.*:after';
        $action = method_exists($event, 'action') ? $event->action() : null;
        $page   = method_exists($event, 'page') ? $event->page() : null;
        $draft  = ($page && method_exists($page, 'isDraft') && $page->isDraft()) ? 'yes' : 'no';
        indexnow_log('Hook seen (no submit): ' . $name . ' action=' . (string)$action . ' draft=' . $draft . ($page ? ' id=' . $page->id() : ''));
    };
}

Kirby::plugin('mynameisfreedom/kirby-indexnow', [
    'panel' => [
        'js' => 'index.js',
        'css' => 'index.css',
    ],
    'hooks' => $indexnowHooks,
    // Panel area with native Panel view; keeps /indexnow-log route as fallback
    'areas' => [
        'indexnow' => function () {
            return [
                'label' => 'IndexNow',
                'icon'  => 'upload',
                'menu'  => true,
                'link'  => 'indexnow',
                'views' => [
                    [
                        'pattern' => 'indexnow',
                        'action'  => function () {
                            return [
                                'title'     => 'IndexNow',
                                'layout'    => 'inside',
                                'component' => 'k-indexnow-view',
                                'props'     => indexnow_panel_props(500),
                            ];
                        }
                    ],
                ],
            ];
        }
    ],
    'routes' => [
        [
            'pattern' => 'indexnow-api',
            'method' => 'POST',
            'action' => function () {
                if (!kirby()->user()) {
                    return new \Kirby\Http\Response('Access denied', 'text/plain', 403);
                }

                $kirby  = kirby();
                $body   = $kirby->request()->body();
                $token  = (string)$body->get('csrf');
                if (csrf($token) !== true) {
                    return new \Kirby\Http\Response('Invalid CSRF token', 'text/plain', 403);
                }

                $action = $body->get('indexnow_action');
                $logPath = $kirby->root('logs') . DIRECTORY_SEPARATOR . 'indexnow.log';

                if ($action === 'clear') {
                    @unlink($logPath);
                    return new \Kirby\Http\Response('ok', 'text/plain', 200);
                }

                if ($action === 'test') {
                    indexnow_log('Manual test entry triggered from Panel');
                    return new \Kirby\Http\Response('ok', 'text/plain', 200);
                }

                return new \Kirby\Http\Response('Unknown action', 'text/plain', 400);
            }
        ],
        [
            'pattern' => 'indexnow-test',
            'action' => function () {
                // Only expose in debug mode to avoid public probing
                if (!kirby()->option('debug')) {
                    return new \Kirby\Http\Response('Disabled', 'text/plain', 403);
                }

                $url = get('url');
                if (!$url) {
                    return new \Kirby\Http\Response("Usage: /indexnow-test?url=https://example.com/path", 'text/plain', 400);
                }

                // Allow debug-only overrides via query to avoid editing config
                $force   = in_array(strtolower((string)get('force')), ['1','true','yes'], true);
                $key     = get('key');
                $keyFile = get('keyFile');
                $endpoint= get('endpoint');

                $overrides = [];
                if ($force)   { $overrides['enabled']  = true; }
                if ($key)     { $overrides['key']      = $key; }
                if ($keyFile) { $overrides['keyFile']  = $keyFile; }
                if ($endpoint){ $overrides['endpoint'] = $endpoint; }

                $code = indexnow_submit([$url], $overrides);
                $msg = 'IndexNow test sent for ' . $url . ' (HTTP ' . ((string)($code ?? 'n/a')) . ')';
                return new \Kirby\Http\Response($msg, 'text/plain', 200);
            }
        ]
    ],
]);