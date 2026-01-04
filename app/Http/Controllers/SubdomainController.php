<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubdomainController extends Controller
{
    public function lookup(Request $request)
    {
        $request->validate([
            'host' => ['required', 'string', 'max:255'],
        ]);

        $host = trim($request->input('host'));

        if (!preg_match('/^[A-Za-z0-9\.\-\[\]:]+$/', $host)) {
            return response()->json(['error' => 'Invalid host format.'], 422);
        }

        $query = urlencode($host);
        $url = "https://crt.sh/?q={$query}&output=json";

        try {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'header' => "User-Agent: gd-log-subdomains/1.0\r\nAccept: application/json\r\n",
                ],
            ];
            $context = stream_context_create($opts);
            $raw = @file_get_contents($url, false, $context);
            if ($raw === false) {
                $last = error_get_last();
                Log::debug('crt.sh returned false for '.$url, ['php_error' => $last]);
                return response()->json(['error' => 'crt.sh lookup failed.'], 502);
            }

            $trimmed = trim($raw);

            if (strlen($trimmed) > 0 && $trimmed[0] === '<') {
                $hdrs = isset($http_response_header) ? $http_response_header : null;
                Log::debug('crt.sh returned HTML for '.$host.'; snippet: '.substr($trimmed,0,200), ['headers' => $hdrs]);
                return response()->json(['error' => 'crt.sh returned non-JSON (HTML) response — possible rate limit or blocking.','raw'=>substr($trimmed,0,400)], 502);
            }

            $arr = json_decode($trimmed, true);
            if (!is_array($arr)) {
                // NDJSON fallback
                $lines = preg_split('/\r\n|\n|\r/', $trimmed);
                $items = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $decoded = json_decode($line, true);
                    if (is_array($decoded)) {
                        $items[] = $decoded;
                    }
                }
                if (!empty($items)) {
                    $arr = $items;
                } else {
                    if (preg_match('/(\[.*\])/s', $trimmed, $m)) {
                        $candidate = $m[1];
                        $arr = json_decode($candidate, true);
                    }
                }
            }

            if (!is_array($arr)) {
                $hdrs = isset($http_response_header) ? $http_response_header : null;
                Log::debug('crt.sh invalid JSON for '.$host.'; raw-snippet: '.substr($trimmed,0,400), ['headers' => $hdrs]);
                return response()->json(['error' => 'Invalid crt.sh response.','raw'=>substr($trimmed,0,400)], 502);
            }

            $subs = [];
            foreach ($arr as $entry) {
                if (isset($entry['name_value'])) {
                    $nv = $entry['name_value'];
                    $parts = preg_split('/\s+|\\n|\n|\r/', $nv);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p === '') continue;
                        $subs[] = $p;
                    }
                }
                if (isset($entry['common_name'])) {
                    $p = trim($entry['common_name']);
                    if ($p !== '') $subs[] = $p;
                }
            }

            // dedupe and filter for host suffix
            $subs = array_unique($subs);
            // optional: filter to entries that end with the host or contain it
            $filtered = array_values(array_filter($subs, function($s) use ($host) {
                return stripos($s, $host) !== false;
            }));

            sort($filtered, SORT_NATURAL | SORT_FLAG_CASE);

            return response()->json(['host'=>$host,'count'=>count($filtered),'subdomains'=>$filtered,'raw'=>$trimmed]);
        } catch (\Throwable $e) {
            Log::debug('crt.sh lookup failed: '.$e->getMessage());
            return response()->json(['error'=>'crt.sh lookup failed.'], 502);
        }
    }
}
