<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CertController extends Controller
{
    public function lookup(Request $request)
    {
        $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $host = trim($request->input('host'));
        $port = (int) ($request->input('port') ?: 443);

        // basic validation
        if (!preg_match('/^[A-Za-z0-9\.\-:\[\]]+$/', $host)) {
            return response()->json(['error' => 'Invalid host format.'], 422);
        }

        // Always attempt a crt.sh Certificate Transparency lookup first.
        try {
            $query = urlencode($host);
            $url = "https://crt.sh/?q={$query}&output=json";
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'header' => "User-Agent: gd-log-cert-fetch/1.0\r\nAccept: application/json\r\n",
                ],
            ];
            $context = stream_context_create($opts);
            $raw = @file_get_contents($url, false, $context);
            if ($raw === false) {
                $last = error_get_last();
                Log::debug('crt.sh returned false for '.$url, ['php_error' => $last]);
            } else {
                $trimmed = trim($raw);

                // Quick check: if HTML returned, crt.sh likely served a human page (rate-limit or no JSON)
                if (strlen($trimmed) > 0 && $trimmed[0] === '<') {
                    $hdrs = isset($http_response_header) ? $http_response_header : null;
                    Log::debug('crt.sh returned HTML for '.$host.'; snippet: '.substr($trimmed,0,200), ['headers' => $hdrs]);
                } else {
                    // Try to decode raw JSON first
                    $arr = json_decode($trimmed, true);

                    // If decode failed, try NDJSON (one JSON object per line)
                    if (!is_array($arr)) {
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
                            // If NDJSON failed, try to extract a JSON array from the response
                            if (preg_match('/(\[.*\])/s', $trimmed, $m)) {
                                $candidate = $m[1];
                                $arr = json_decode($candidate, true);
                            }
                        }
                    }

                    if (is_array($arr)) {
                        // limit results to 100 entries
                        $arr = array_slice($arr, 0, 100);
                        if (!empty($arr)) {
                            return response()->json(['host' => $host, 'crt_sh' => $arr, 'raw' => $trimmed]);
                        }
                    } else {
                        $hdrs = isset($http_response_header) ? $http_response_header : null;
                        Log::debug('crt.sh invalid JSON for '.$host.'; raw-snippet: '.substr($trimmed,0,400), ['headers' => $hdrs]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('crt.sh lookup failed: '.$e->getMessage());
        }

        $timeout = 5;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'capture_peer_chain' => true,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $remote = 'tcp://' . $host . ':' . $port;

        set_error_handler(function () { /* silence warnings */ });
        $client = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        restore_error_handler();

        if (! $client) {
            Log::debug('Cert lookup failed: '.$errstr.' ('.$errno.')');
            return response()->json(['error' => 'Unable to connect to host: ' . $errstr], 502);
        }

        $params = stream_context_get_params($client);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        $chain = $params['options']['ssl']['peer_certificate_chain'] ?? null;

        if (! $cert) {
            return response()->json(['error' => 'No certificate returned by peer.'], 502);
        }

        $parsed = @openssl_x509_parse($cert, false);

        $response = [
            'host' => $host,
            'port' => $port,
            'parsed' => $parsed ?: null,
            'pem' => openssl_x509_export($cert, $out) ? $out : null,
        ];

        // attach chain if available
        if (is_array($chain)) {
            $chainOut = [];
            foreach ($chain as $c) {
                if (is_resource($c) || is_string($c)) {
                    openssl_x509_export($c, $cout);
                    $chainOut[] = $cout;
                }
            }
            $response['chain'] = $chainOut;
        }

        fclose($client);

        return response()->json($response);
    }
}
