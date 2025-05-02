<?php

/**
 * Aura.Router Performance Benchmark with Memory Usage Tracking
 *
 * This script benchmarks Aura.Router performance (matching time and peak memory usage)
 * with varying numbers of routes, comparing the current implementation with a baseline.
 *
 * Usage:
 * 1. Install Aura.Router and a PSR-7 implementation using Composer:
 *    composer require aura/router laminas/laminas-diactoros
 * 2. Run this script: php benchmark.php
 *
 * Options:
 *  --save              Save the results as benchmark_baseline.json
 *  --baseline <file>   Compare results against the specified baseline JSON file
 *                      (default: benchmark_baseline.json)
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// ----------------------------------------
// Configuration
// ----------------------------------------

// Define the number of routes to test
const ROUTE_COUNTS = [10, 50, 100, 200, 500, 1000, 2000];

// Number of iterations for each test path matching
const TEST_ITERATIONS = 100;

// Types of routes to generate (adjust percentages as needed)
const ROUTE_TYPES = [
    'static'        => 0.15,
    'simple_param'  => 0.25,
    'complex_param' => 0.15,
    'optional'      => 0.10,
    'api_style'     => 0.35,
];

// URL paths to test with (matching and non-matching)
const TEST_PATHS = [
    // Static paths
    '/users/list', '/products', '/about/company', '/admin/dashboard-123', '/blog/some-static-path-456',
    // Paths with parameters
    '/users/42', '/products/electronics/laptops', '/blog/2023/04/some-article', '/articles/my-cool-slug', '/categories/tech/gadgets',
    // Paths with complex parameters
    '/users/999', '/blog/2024/12/31', '/users/username/testuser123', '/orders/12345.json',
    // Paths with optional parameters
    '/archive', '/archive/2023', '/archive/2023/11', '/archive/2023/11/05', '/shop/books', '/shop/books/fiction',
    // API style paths
    '/api/v1/users', '/api/v1/users/123', '/api/v2/posts/456/comments', '/api/v3/products/789/reviews/101',
    '/api/v1/customers/555/orders', '/api/v2/accounts/111/history/222', '/api/v1/posts/333/activate', '/api/v3/users/777/archive',
    // Paths that should not match any route
    '/this/path/does/not/exist', '/api/v4/nonexistent', '/users/profile/edit/extra',
];

// ----------------------------------------
// Helper functions for route generation
// ----------------------------------------
/**
 * Generates API-style routes with nested resources
 * Note: This function generates multiple route patterns for a single "resource count" unit.
 * The actual number of generated routes will be higher than $resourceCount.
 *
 * @param int $resourceCount Number of base resources to generate variations for
 * @return array Array of routes [routeName => pattern]
 */
function generateApiStyleRoutes($resourceCount) {
    $apiVersions = ['v1', 'v2', 'v3'];
    $resources = ['users', 'posts', 'comments', 'products', 'orders', 'customers', 'accounts', 'categories', 'items', 'invoices', 'payments'];
    $subresources = ['comments', 'likes', 'tags', 'reviews', 'ratings', 'images', 'followers', 'history', 'items', 'details', 'logs'];
    $actions = ['activate', 'deactivate', 'archive', 'publish', 'favorite', 'share', 'report', 'verify', 'approve', 'reject', 'resend'];

    $routes = [];
    $generatedRouteNames = []; // Keep track of generated names to avoid duplicates

    for ($i = 0; $i < $resourceCount; $i++) {
        $version = $apiVersions[array_rand($apiVersions)];
        $resource = $resources[array_rand($resources)];

        // Base route pattern (List)
        $basePattern = "/api/{$version}/{$resource}";
        $baseRouteName = "api_{$version}_{$resource}_list";
        if (!isset($generatedRouteNames[$baseRouteName])) {
            $routes[$baseRouteName] = $basePattern;
            $generatedRouteNames[$baseRouteName] = true;
        }

        // Resource with ID (Get)
        $idPattern = "/api/{$version}/{$resource}/{id}";
        $idRouteName = "api_{$version}_{$resource}_get";
        if (!isset($generatedRouteNames[$idRouteName])) {
            $routes[$idRouteName] = $idPattern;
            $generatedRouteNames[$idRouteName] = true;
        }

        // Add some sub-resources (nested)
        if (rand(0, 3) > 0) { // 75% chance
            $subresource = $subresources[array_rand($subresources)];

            // Sub-resource list
            $sublistPattern = "/api/{$version}/{$resource}/{id}/{$subresource}";
            $sublistRouteName = "api_{$version}_{$resource}_{$subresource}_list";
            if (!isset($generatedRouteNames[$sublistRouteName])) {
                $routes[$sublistRouteName] = $sublistPattern;
                $generatedRouteNames[$sublistRouteName] = true;
            }

            // Sub-resource with ID (Get)
            $subIdPattern = "/api/{$version}/{$resource}/{id}/{$subresource}/{subid}";
            $subIdRouteName = "api_{$version}_{$resource}_{$subresource}_get";
            if (!isset($generatedRouteNames[$subIdRouteName])) {
                $routes[$subIdRouteName] = $subIdPattern;
                $generatedRouteNames[$subIdRouteName] = true;
            }
        }

        // Add some action routes for the main resource
        if (rand(0, 3) > 1) { // 50% chance
            $action = $actions[array_rand($actions)];
            $actionPattern = "/api/{$version}/{$resource}/{id}/{$action}";
            $actionRouteName = "api_{$version}_{$resource}_action_{$action}";
            if (!isset($generatedRouteNames[$actionRouteName])) {
                $routes[$actionRouteName] = $actionPattern;
                $generatedRouteNames[$actionRouteName] = true;
            }
        }
    }

    // Add some top-level API action routes (e.g., /api/v1/health, /api/v2/status)
    $topLevelActions = ['health', 'status', 'ping', 'info'];
    for ($k = 0; $k < min($resourceCount / 5, 10); $k++) { // Add a few top-level actions
        $version = $apiVersions[array_rand($apiVersions)];
        $action = $topLevelActions[array_rand($topLevelActions)];
        $pattern = "/api/{$version}/{$action}";
        $routeName = "api_{$version}_toplevel_{$action}";
        if (!isset($generatedRouteNames[$routeName])) {
            $routes[$routeName] = $pattern;
            $generatedRouteNames[$routeName] = true;
        }
    }


    return $routes;
}


/**
 * Generate a random static path with the given depth
 *
 * @param int $depth Number of segments
 * @return string The generated path
 */
function generateStaticPath($depth) {
    $segments = [
        'users', 'products', 'blog', 'news', 'about', 'contact',
        'services', 'support', 'docs', /*'api',*/ 'account', 'settings', // Removed 'api' to avoid clash with API routes
        'categories', 'tags', 'search', 'help', 'faq', 'privacy',
        'terms', 'admin', 'dashboard', 'reports', 'stats', 'profile',
        'jobs', 'partners', 'investors', 'media', 'press', 'legal',
    ];

    $path = '';

    for ($i = 0; $i < $depth; $i++) {
        $segment = $segments[array_rand($segments)];
        $path .= "/{$segment}";

        // Add some uniqueness to avoid duplicate routes
        if ($i === $depth - 1) {
            $path .= '-' . rand(100, 9999); // Increased range for uniqueness
        }
    }
    // Ensure path starts with /
    if (empty($path)) $path = '/' . $segments[array_rand($segments)] . '-' . rand(100, 9999);
    if ($path[0] !== '/') $path = '/' . $path;

    return $path;
}

/**
 * Generate a path with simple parameters
 *
 * @param int $depth Number of segments
 * @return string The generated path
 */
function generatePathWithSimpleParams($depth) {
    $staticSegments = [
        'users', 'products', 'blog', 'news', 'articles', 'categories',
        'tags', 'comments', 'posts', 'orders', 'customers', 'items',
        'events', 'groups', 'forums', 'threads', 'messages', 'files',
    ];

    $paramSegments = [
        'id', 'user_id', 'product_id', 'slug', 'category', 'tag',
        'year', 'month', 'day', 'page', 'type', 'format',
        'uuid', 'token', 'hash', 'key', 'name', 'code',
    ];

    $path = '';
    $usedParams = []; // Track used parameter names to avoid duplicates in a single path

    for ($i = 0; $i < $depth; $i++) {
        // Decide whether to add a static or param segment
        // Increase chance of param towards the end, ensure at least one static if depth > 1
        $isParam = ($i > 0 && rand(0, $depth) >= $i) || ($i == $depth -1 && $depth > 0);

        if ($isParam) {
            // Add a parameter segment
            $availableParams = array_diff($paramSegments, $usedParams);
            if (empty($availableParams)) {
                $param = $paramSegments[array_rand($paramSegments)] . '_' . $i; // Ensure uniqueness if base names run out
            } else {
                $param = $availableParams[array_rand($availableParams)];
            }
            $usedParams[] = $param; // Mark this base name as used for this path
            $path .= "/{" . $param . "}";
        } else {
            // Add a static segment
            $segment = $staticSegments[array_rand($staticSegments)];
            $path .= "/{$segment}";
        }
    }
    // Ensure path starts with /
    if (empty($path)) $path = '/' . $staticSegments[array_rand($staticSegments)];
    if ($path[0] !== '/') $path = '/' . $path;


    return $path;
}

/**
 * Generate a path with complex parameters (using regex constraints)
 *
 * @param int $depth Number of segments
 * @return string The generated path
 */
function generatePathWithComplexParams($depth) {
    $staticSegments = [
        'users', 'products', 'blog', 'orders', 'articles', 'categories',
        'files', 'images', 'videos', 'downloads', 'search', 'query',
    ];

    $complexParams = [
        'id:(\d+)', // Numeric ID
        'uid:([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})', // UUID
        'year:(\d{4})',
        'month:(0[1-9]|1[0-2])',
        'day:(0[1-9]|[12]\d|3[01])',
        'slug:([a-z0-9]+(?:-[a-z0-9]+)*)', // Slug (lowercase letters, numbers, hyphens)
        'username:([a-zA-Z0-9_]{3,16})', // Username (letters, numbers, underscore)
        'hash:([a-f0-9]{32})', // MD5 Hash (example)
        'format:(\.(?:json|xml|html|csv))?', // Optional format extension
        'page:(\d+)',
        'version:(v\d+(?:\.\d+)*)', // Version like v1, v2.1 etc.
    ];

    $path = '';
    $usedParams = []; // Track used parameter base names to avoid duplicates in a single path

    for ($i = 0; $i < $depth; $i++) {
        // Decide whether to add a static or param segment
        $isParam = ($i > 0 && rand(0, $depth) >= $i) || ($i == $depth -1 && $depth > 0);

        if ($isParam) {
            // Add a complex parameter
            $availableParams = [];
            foreach ($complexParams as $paramPattern) {
                $baseName = explode(':', $paramPattern)[0];
                if (!in_array($baseName, $usedParams)) {
                    $availableParams[] = $paramPattern;
                }
            }

            if (empty($availableParams)) {
                // If all base names are used, pick one and add suffix for uniqueness
                $paramPattern = $complexParams[array_rand($complexParams)];
                $parts = explode(':', $paramPattern);
                $paramName = $parts[0] . '_' . $i;
                $paramRegex = $parts[1];
                $param = $paramName . ':' . $paramRegex;
            } else {
                $param = $availableParams[array_rand($availableParams)];
                $paramName = explode(':', $param)[0];
            }

            $usedParams[] = $paramName; // Mark this base name as used
            $path .= "/{" . $param . "}";
        } else {
            // Add a static segment
            $segment = $staticSegments[array_rand($staticSegments)];
            $path .= "/{$segment}";
        }
    }
    // Ensure path starts with /
    if (empty($path)) $path = '/' . $staticSegments[array_rand($staticSegments)];
    if ($path[0] !== '/') $path = '/' . $path;

    return $path;
}

/**
 * Generate a path with optional parameters
 *
 * @return string The generated path
 */
function generatePathWithOptionalParams() {
    $baseSegments = [
        'archive', 'blog', 'reports', 'gallery', 'shop',
        'search', 'filter', 'browse', 'explore',
    ];

    // Make sure each param name is unique within its set
    $optionalParamSets = [
        ['year', 'month', 'day'],
        ['category', 'subcategory', 'item'],
        ['type', 'format', 'layout'],
        ['lang', 'region', 'theme'],
        ['sort', 'order', 'limit', 'offset'],
        ['filter_a', 'filter_b', 'filter_c'],
        ['option1', 'option2', 'option3'],
    ];

    $base = $baseSegments[array_rand($baseSegments)];
    $params = $optionalParamSets[array_rand($optionalParamSets)];

    // Randomly decide the number of optional segments (1 to all)
    $numOptional = rand(1, count($params));
    $selectedParams = array_slice($params, 0, $numOptional);


    $path = "/{$base}{/";
    $path .= implode(',', $selectedParams);
    $path .= "}";

    // Add uniqueness to the base path
    $path .= '-' . rand(100, 999);

    return $path;
}
// ----------------------------------------
// Benchmark functions
// ----------------------------------------

/**
 * Creates a router container for testing
 */
function createRouterContainer() {
    // gc_collect_cycles(); // Optional: Might help with memory consistency
    return new \Aura\Router\RouterContainer();
}

/**
 * Generates routes for testing
 *
 * @param \Aura\Router\Map $map The route map
 * @param int $count Target number of routes to generate
 * @return int Actual number of routes generated
 */
function generateRoutes($map, $count) {
    $routeTypes = getRouteDistribution($count);
    $generatedCount = 0;

    $routeGenerator = function($routeName, $path) use ($map, &$generatedCount) {
        try {
            $map->getRoute($routeName);
        } catch (\Aura\Router\Exception\RouteNotFound $e) {
            $map->get($routeName, $path)->handler(function() use ($routeName) { return "Handler for {$routeName}"; });
            $generatedCount++;
        } catch (\Exception $e) {
            echo "Warning: Error checking or adding route '{$routeName}'. Error: " . $e->getMessage() . "\n";
        }
    };

    // Generate static routes
    for ($i = 0; $i < $routeTypes['static']; $i++) {
        $routeGenerator("static_{$i}", generateStaticPath(rand(1, 4)));
    }
    // Generate routes with simple parameters
    for ($i = 0; $i < $routeTypes['simple_param']; $i++) {
        $routeGenerator("simple_param_{$i}", generatePathWithSimpleParams(rand(1, 4)));
    }
    // Generate routes with complex parameters
    for ($i = 0; $i < $routeTypes['complex_param']; $i++) {
        $routeGenerator("complex_param_{$i}", generatePathWithComplexParams(rand(1, 3)));
    }
    // Generate routes with optional parameters
    for ($i = 0; $i < $routeTypes['optional']; $i++) {
        $routeGenerator("optional_{$i}", generatePathWithOptionalParams());
    }
    // Generate API Style Routes
    $apiStyleTargetCount = $routeTypes['api_style'];
    $apiResourceCount = max(1, floor($apiStyleTargetCount / 3.0));
    $apiRoutes = generateApiStyleRoutes($apiResourceCount);
    foreach ($apiRoutes as $routeName => $path) {
        $routeGenerator("api_style_" . $routeName, $path);
    }
    // Add routes with common prefixes
    if ($count >= 100) {
        $prefixes = ['admin', 'blog', 'support'];
        foreach ($prefixes as $prefix) {
            $prefixRouteCount = min(50, max(5, floor($count * (0.05 + rand(-2, 2)/100))));
            for ($i = 0; $i < $prefixRouteCount; $i++) {
                $routeGenerator("prefix_{$prefix}_static_{$i}", "/{$prefix}" . generateStaticPath(rand(1, 3)));
                $routeGenerator("prefix_{$prefix}_param_{$i}", "/{$prefix}" . generatePathWithSimpleParams(rand(1, 3)));
            }
        }
    }
    return $generatedCount; // Return the actual count
}


/**
 * Run the benchmark (time measurement part) for a given route count
 *
 * @param int $routeCount Target number of routes to benchmark with
 * @return array Path timing results [path => ['avg' => ..., 'min' => ..., 'max' => ..., 'matched' => ...]]
 */
function runBenchmark($routeCount) {
    if (!class_exists('\Laminas\Diactoros\ServerRequestFactory')) {
        echo "\nError: PSR-7 implementation not found. Please install laminas/laminas-diactoros.\n"; exit(1);
    }

    $container = createRouterContainer();
    $map = $container->getMap();

    echo "Generating routes targeting {$routeCount}... ";
    $actualGeneratedCount = generateRoutes($map, $routeCount);
    echo "Generated {$actualGeneratedCount} routes. Done!\n";

    $matcher = $container->getMatcher();
    $pathResults = [];

    echo "Running path matching benchmark (Iterations: " . TEST_ITERATIONS . ")...\n";

    foreach (TEST_PATHS as $path) {
        $times = [];
        $matchedStatus = false; // Track matched status

        for ($i = 0; $i < TEST_ITERATIONS; $i++) {
            $server = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path, 'PATH_INFO' => $path, 'REQUEST_SCHEME' => 'http', 'HTTP_HOST' => 'example.com', 'SERVER_NAME' => 'example.com', 'SERVER_PORT' => 80];
            $request = \Laminas\Diactoros\ServerRequestFactory::fromGlobals($server, [], [], [], []);

            $start = microtime(true);
            $route = $matcher->match($request);
            $end = microtime(true);

            $times[] = ($end - $start) * 1000; // Milliseconds
            if ($i === 0) { // Check matched status only once per path
                $matchedStatus = (bool)$route;
            }
        }

        sort($times);
        $numTimes = count($times);
        $trimmedTimes = $times;
        if ($numTimes >= 20) {
            $outlierCount = floor($numTimes * 0.1);
            $trimmedTimes = array_slice($times, $outlierCount, $numTimes - $outlierCount * 2);
        }

        $avg = $min = $max = 0.0;
        if (count($trimmedTimes) > 0) {
            $avg = array_sum($trimmedTimes) / count($trimmedTimes);
            $min = min($trimmedTimes);
            $max = max($trimmedTimes);
        } else if ($numTimes > 0) {
            $avg = array_sum($times) / $numTimes; $min = min($times); $max = max($times);
            echo "Warning: Could not trim outliers for path '{$path}'.\n";
        }

        $pathResults[$path] = ['avg' => $avg, 'min' => $min, 'max' => $max, 'matched' => $matchedStatus];
    }
    echo "Path matching benchmark finished for {$routeCount} routes.\n";
    return $pathResults;
}

/**
 * Helper function to determine route distribution based on percentages
 * @param int $count Total number of routes
 * @return array Number of routes per type
 */
function getRouteDistribution($count) {
    $distribution = []; $remaining = $count; $totalPercentage = array_sum(ROUTE_TYPES);
    if (abs($totalPercentage - 1.0) > 0.01) {
        echo "Warning: ROUTE_TYPES percentages sum to {$totalPercentage}. Normalizing...\n";
        foreach (ROUTE_TYPES as $type => $percentage) {
            $normalizedPercentage = ($totalPercentage > 0) ? $percentage / $totalPercentage : 0;
            $typeCount = floor($count * $normalizedPercentage);
            $distribution[$type] = $typeCount; $remaining -= $typeCount;
        }
    } else {
        $types = array_keys(ROUTE_TYPES);
        foreach ($types as $index => $type) {
            if ($index === count($types) - 1) { $distribution[$type] = max(0, $remaining); }
            else { $percentage = ROUTE_TYPES[$type]; $typeCount = floor($count * $percentage); $distribution[$type] = $typeCount; $remaining -= $typeCount; }
        }
    }
    if ($remaining > 0 && $count > 0) {
        $totalCalculated = array_sum($distribution);
        if ($totalCalculated > 0 && $totalPercentage > 0) {
            $distributedRemainder = 0; $types = array_keys(ROUTE_TYPES);
            foreach ($types as $type) {
                $proportion = ROUTE_TYPES[$type] / $totalPercentage; $share = round($remaining * $proportion);
                $distribution[$type] += $share; $distributedRemainder += $share;
            }
            $finalDifference = $remaining - $distributedRemainder;
            if ($finalDifference != 0 && !empty($distribution)) { $distribution[array_key_first($distribution)] += $finalDifference; }
        } elseif (!empty($distribution)) { $distribution[array_key_first($distribution)] += $remaining; }
    }
    return $distribution;
}

/**
 * Formats bytes into a human-readable string (KB, MB).
 * @param int $bytes
 * @return string
 */
function formatBytes(int $bytes): string {
    if ($bytes > 1024 * 1024) {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    } elseif ($bytes > 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}


/**
 * Format and display the benchmark results (including memory)
 *
 * @param array $allResults The benchmark results for all route counts [routeCount => ['paths' => [...], 'memory_peak' => ...]]
 * @param array|null $baselineResults Optional baseline results for comparison
 */
function displayResults($allResults, $baselineResults = null) {
    echo "\n============================================\n";
    echo "BENCHMARK RESULTS\n";
    echo "============================================\n";

    $timePrecision = 6; // For milliseconds

    // --- Time Results ---
    echo "\nAverage matching time by target route count:\n";
    echo "--------------------------------------------\n";
    if ($baselineResults) {
        echo "Route Count | Current Avg (ms)   | Baseline Avg (ms)  | Improvement (%)\n";
        echo "------------|--------------------|--------------------|----------------\n";
        foreach ($allResults as $routeCount => $resultData) {
            $pathResults = $resultData['paths'] ?? []; // Access path results safely
            $currentAvg = count($pathResults) > 0 ? array_sum(array_column($pathResults, 'avg')) / count($pathResults) : 0;

            if (!isset($baselineResults[$routeCount])) {
                printf("%11d | %*.*f | Baseline Missing   | N/A\n", $routeCount, 18, $timePrecision, $currentAvg); continue;
            }
            // Access baseline path results correctly and safely
            $baselinePathResults = $baselineResults[$routeCount]['paths'] ?? [];
            $baselineAvg = count($baselinePathResults) > 0 ? array_sum(array_column($baselinePathResults, 'avg')) / count($baselinePathResults) : 0;
            $improvement = ($baselineAvg > 1e-9) ? (($baselineAvg - $currentAvg) / $baselineAvg) * 100 : ($currentAvg < 1e-9 ? 0 : -INF);
            printf("%11d | %*.*f | %*.*f | %+14.2f%%\n", $routeCount, 18, $timePrecision, $currentAvg, 18, $timePrecision, $baselineAvg, $improvement);
        }
    } else {
        echo "Route Count | Avg Time (ms)    | Avg Matched (ms) | Avg Not Matched (ms)\n";
        echo "------------|------------------|------------------|---------------------\n";
        foreach ($allResults as $routeCount => $resultData) {
            $pathResults = $resultData['paths'] ?? []; $avgTime = $avgMatchedTime = $avgNonMatchedTime = 0.0;
            if (count($pathResults) > 0) {
                $avgTime = array_sum(array_column($pathResults, 'avg')) / count($pathResults);
                $matchedPaths = array_filter($pathResults, fn($r) => $r['matched']);
                $nonMatchedPaths = array_filter($pathResults, fn($r) => !$r['matched']);
                if (count($matchedPaths) > 0) $avgMatchedTime = array_sum(array_column($matchedPaths, 'avg')) / count($matchedPaths);
                if (count($nonMatchedPaths) > 0) $avgNonMatchedTime = array_sum(array_column($nonMatchedPaths, 'avg')) / count($nonMatchedPaths);
            }
            printf("%11d | %*.*f | %*.*f | %*.*f\n", $routeCount, 16, $timePrecision, $avgTime, 16, $timePrecision, $avgMatchedTime, 19, $timePrecision, $avgNonMatchedTime);
        }
    }

    // --- Memory Results ---
    echo "\nPeak memory usage by target route count:\n";
    echo "--------------------------------------------\n";
    if ($baselineResults) {
        echo "Route Count | Current Peak | Baseline Peak | Reduction (%)\n";
        echo "------------|--------------|---------------|---------------\n";
        foreach ($allResults as $routeCount => $resultData) {
            $currentPeak = $resultData['memory_peak'] ?? 0; // Use null coalesce
            $currentPeakFormatted = formatBytes($currentPeak);

            if (!isset($baselineResults[$routeCount])) {
                printf("%11d | %12s | Baseline Miss | N/A\n", $routeCount, $currentPeakFormatted); continue;
            }
            $baselinePeak = $baselineResults[$routeCount]['memory_peak'] ?? 0; // Use null coalesce
            $baselinePeakFormatted = formatBytes($baselinePeak);
            // Calculate reduction percentage (positive means less memory used)
            $reduction = ($baselinePeak > 0) ? (($baselinePeak - $currentPeak) / $baselinePeak) * 100 : ($currentPeak == 0 ? 0 : -INF);
            printf("%11d | %12s | %13s | %+13.2f%%\n", $routeCount, $currentPeakFormatted, $baselinePeakFormatted, $reduction);
        }
    } else {
        echo "Route Count | Peak Memory\n";
        echo "------------|-------------\n";
        foreach ($allResults as $routeCount => $resultData) {
            printf("%11d | %11s\n", $routeCount, formatBytes($resultData['memory_peak'] ?? 0));
        }
    }


    // --- Detailed Path Timings for Max Route Count ---
    if (!empty($allResults)) {
        $maxRouteCount = max(array_keys($allResults));
        $maxRouteResults = $allResults[$maxRouteCount]['paths'] ?? []; // Use null coalesce

        echo "\nDetailed path timing results for target route count {$maxRouteCount}:\n";
        echo "-----------------------------------------------------------------\n";
        $baselineMaxRouteData = $baselineResults[$maxRouteCount]['paths'] ?? null; // Use null coalesce
        if ($baselineMaxRouteData) {
            echo "Path                               | Matched | Avg Time (ms)    | Baseline (ms)    | Improvement (%)\n";
            echo "-----------------------------------|---------|------------------|------------------|----------------\n";
        } else {
            echo "Path                               | Matched | Avg Time (ms)\n";
            echo "-----------------------------------|---------|------------------\n";
        }
        ksort($maxRouteResults); // Sort by path name
        foreach ($maxRouteResults as $path => $result) {
            $matched = $result['matched'] ? 'Yes' : 'No ';
            printf("%-35s | %-7s | %*.*f", $path, $matched, 16, $timePrecision, $result['avg']);
            if ($baselineMaxRouteData && isset($baselineMaxRouteData[$path])) {
                $baselineResult = $baselineMaxRouteData[$path];
                $improvement = ($baselineResult['avg'] > 1e-9) ? (($baselineResult['avg'] - $result['avg']) / $baselineResult['avg']) * 100 : ($result['avg'] < 1e-9 ? 0 : -INF);
                printf(" | %*.*f | %+14.2f%%", 16, $timePrecision, $baselineResult['avg'], $improvement);
            }
            echo "\n";
        }

        // API path subset (already included above, just for focused view)
        $apiPaths = array_filter(array_keys($maxRouteResults), fn($p) => strpos($p, '/api/') === 0);
        if (count($apiPaths) > 0) {
            echo "\nAPI path timing results for target route count {$maxRouteCount} (subset):\n";
            if ($baselineMaxRouteData) {
                echo "API Path                           | Matched | Avg Time (ms)    | Baseline (ms)    | Improvement (%)\n";
                echo "-----------------------------------|---------|------------------|------------------|----------------\n";
            } else {
                echo "API Path                           | Matched | Avg Time (ms)\n";
                echo "-----------------------------------|---------|------------------\n";
            }
            $apiPathResults = array_intersect_key($maxRouteResults, array_flip($apiPaths));
            ksort($apiPathResults);
            foreach ($apiPathResults as $path => $result) {
                $matched = $result['matched'] ? 'Yes' : 'No ';
                printf("%-35s | %-7s | %*.*f", $path, $matched, 16, $timePrecision, $result['avg']);
                if ($baselineMaxRouteData && isset($baselineMaxRouteData[$path])) {
                    $baselineResult = $baselineMaxRouteData[$path];
                    $improvement = ($baselineResult['avg'] > 1e-9) ? (($baselineResult['avg'] - $result['avg']) / $baselineResult['avg']) * 100 : ($result['avg'] < 1e-9 ? 0 : -INF);
                    printf(" | %*.*f | %+14.2f%%", 16, $timePrecision, $baselineResult['avg'], $improvement);
                }
                echo "\n";
            }
        }
    }
    echo "\n";
}

// ----------------------------------------
// Main benchmark script
// ----------------------------------------

echo "Aura.Router Performance Benchmark (with Memory Usage)\n";
echo "==================================================\n\n";

// Dependency checks
if (!class_exists('\Aura\Router\RouterContainer')) { echo "Error: Aura.Router not installed.\n"; exit(1); }
if (!class_exists('\Laminas\Diactoros\ServerRequestFactory')) { echo "Error: laminas/laminas-diactoros not installed.\n"; exit(1); }

// Display config
echo "Configuration:\n";
echo "  - Target Route Counts: " . implode(', ', ROUTE_COUNTS) . "\n";
echo "  - Test Iterations per Path: " . TEST_ITERATIONS . "\n";
echo "  - Test Paths: " . count(TEST_PATHS) . "\n";
echo "  - Route Type Distribution:\n";
foreach(ROUTE_TYPES as $type => $perc) printf("    - %-15s: %.2f%%\n", $type, $perc * 100);
echo "\n";

// Baseline handling (Corrected logic)
$baselineFile = __DIR__ . '/benchmark_baseline.json'; // Default load/save location
$baselineResults = null;
$saveAsBaseline = false;
$specifiedBaselinePath = null;

// Correct getopt definition: 's' takes no value, 'b' requires a value.
$options = getopt('b:s', ['baseline:', 'save']);

// Check if a baseline path was specified for loading or saving
if (isset($options['b']) || isset($options['baseline'])) {
    $specifiedBaselinePath = $options['b'] ?? $options['baseline'];
}

// Determine if we need to save
if (isset($options['s']) || isset($options['save'])) {
    $saveAsBaseline = true;
    // If a path was specified via -b/--baseline, use that for saving. Otherwise, use the default.
    if ($specifiedBaselinePath !== null && is_string($specifiedBaselinePath) && $specifiedBaselinePath !== '') {
        $baselineFile = $specifiedBaselinePath; // Use the specified path for saving
        echo "Results will be saved as baseline to specified path: {$baselineFile}\n";
    } else {
        // Use the default path for saving
        echo "Results will be saved as baseline to default path: {$baselineFile}\n";
    }
}

// Load baseline results if NOT saving
if (!$saveAsBaseline) {
    $loadPath = $specifiedBaselinePath ?? $baselineFile; // Path to load from (specified or default)
    if (file_exists($loadPath)) {
        echo "Loading baseline from: {$loadPath}\n";
        $baselineJson = file_get_contents($loadPath);
        if ($baselineJson !== false) {
            $baselineResults = json_decode($baselineJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "Error decoding baseline JSON from {$loadPath}: " . json_last_error_msg() . "\n"; $baselineResults = null;
            }
        } else {
            echo "Error reading baseline file: {$loadPath}\n";
        }
    } else {
        // Only report 'not found' if a path was explicitly specified or if the default file was expected
        if ($specifiedBaselinePath !== null) {
            echo "Specified baseline file not found: {$specifiedBaselinePath}\n";
        } else {
            echo "Default baseline file not found: {$baselineFile}. Proceeding without baseline comparison.\n";
        }
    }
}


// --- Run Benchmarks ---
$allResults = [];
$initialMemoryPeak = memory_get_peak_usage(true);

foreach (ROUTE_COUNTS as $routeCount) {
    // Run the benchmark for path timings
    $pathResults = runBenchmark($routeCount);

    // Get the peak memory usage AFTER running the benchmark for this route count
    $memoryPeakForRun = memory_get_peak_usage(true);

    // Store both timing and memory results
    $allResults[$routeCount] = [
        'paths' => $pathResults,
        'memory_peak' => $memoryPeakForRun,
    ];
    echo "Peak Memory after {$routeCount} routes run: " . formatBytes($memoryPeakForRun) . "\n";
    echo "\n"; // Separator between route count runs
}

// Display results
displayResults($allResults, $baselineResults);

// Save results as baseline if requested
if ($saveAsBaseline) {
    // Ensure $baselineFile is a non-empty string before trying to save
    if (empty($baselineFile) || !is_string($baselineFile)) {
        echo "Error: Cannot save baseline. Invalid or empty file path provided.\n";
    } else {
        $jsonResults = json_encode($allResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Line 740 approx was here
            if (file_put_contents($baselineFile, $jsonResults) !== false) {
                echo "Results saved as baseline to: {$baselineFile}\n";
            } else { echo "Error: Failed to write baseline file: {$baselineFile}\n"; }
        } else { echo "Error: Failed to encode results to JSON: " . json_last_error_msg() . "\n"; }
    }
}

// Final tips
echo "\nBenchmark finished.\n";
if (!$saveAsBaseline && !$baselineResults) {
    echo "Tip: Run with `--save` to create a baseline.\n";
} elseif ($saveAsBaseline) {
    echo "Tip: After making changes, run again using `--baseline {$baselineFile}` to compare.\n";
}
