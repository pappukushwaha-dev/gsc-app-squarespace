<?php
// mcp-config.php — MCP server configuration
declare(strict_types=1);

header('Content-Type: application/json');

$config = [
    'platform' => 'squarespace',
    'mcp_endpoint' => 'https://your-domain.com/gsc-mcp',
    'tools' => [
        'search_analytics',
        'list_properties',
        'list_sitemaps',
        'growth_opportunities',
        'find_cannibalization',
    ],
];

echo json_encode($config, JSON_PRETTY_PRINT);
