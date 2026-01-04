<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhoisController extends Controller
{
    public function lookup(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = trim($request->input('domain'));
        $domain = preg_replace('/\s+/', '', $domain);
        $domain = strtolower($domain);

        if (!preg_match('/^[a-z0-9\-\.]+$/', $domain)) {
            return response()->json(['error' => 'Invalid domain format.'], 422);
        }

        // Use PHP socket-based WHOIS lookup (no external binaries)
        try {
            $output = $this->socketWhois($domain);
        } catch (\Throwable $e) {
            Log::debug('Whois socket failed: '.$e->getMessage());
            return response()->json(['error' => 'Whois lookup failed.'], 500);
        }

        // Cap the response size to avoid extremely large payloads
        $max = 20000; // characters
        if (is_string($output) && strlen($output) > $max) {
            $output = substr($output, 0, $max) . "\n\n...truncated...";
        }

        return response()->json(['domain' => $domain, 'whois' => trim($output ?: '')]);
    }

    protected function socketWhois(string $domain): string
    {
        $tld = Str::afterLast($domain, '.');
        $server = 'whois.iana.org';

        $result = $this->queryWhoisServer($server, $domain);

        // try to parse referral
        if (preg_match('/refer:\s*(\S+)/i', $result, $m)) {
            $ref = $m[1];
            $refResult = $this->queryWhoisServer($ref, $domain);
            return $result."\n\n--- Referral ($ref) ---\n\n".$refResult;
        }

        return $result;
    }

    protected function queryWhoisServer(string $server, string $domain): string
    {
        $out = '';
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($server, 43, $errno, $errstr, 5);
        if (! $fp) {
            throw new \RuntimeException('Unable to connect to whois server: '.$server);
        }
        fwrite($fp, $domain."\r\n");
        while (!feof($fp)) {
            $out .= fgets($fp, 1024);
        }
        fclose($fp);
        return $out;
    }
}
