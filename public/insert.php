<?php

/**
 * DEPRECATED GPS HTTP endpoint.
 *
 * NEW ARCHITECTURE:
 *   Traccar server receives GPS packets and writes them to tc_positions.
 *   This endpoint used to push packets to a Redis stack that Laravel's
 *   insert:run worker would then write into per-device positions_<id> tables.
 *
 *   That pipeline is now disabled. GPS devices must be pointed at the
 *   Traccar server, not at this URL.
 *
 *   We still return HTTP 200 with {status: 1} so that any GPS device that
 *   happens to hit this endpoint does not retry endlessly. No data is stored.
 */

// Log the hit so admins can spot devices still pointed at the old endpoint.
$logLine = date('Y-m-d H:i:s') . ' ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-') . PHP_EOL;
@file_put_contents(__DIR__ . '/../storage/logs/legacy_insert_hits.log', $logLine, FILE_APPEND);

header('Content-Type: application/json');
echo json_encode(['status' => 1, 'message' => 'Deprecated endpoint. Point device at Traccar server.']);
die();