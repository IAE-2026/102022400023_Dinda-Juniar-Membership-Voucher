<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use App\Models\User;

class AuditSoapController extends Controller
{
    public function handle(Request $request)
    {
        $raw = $request->getContent();
        if (empty($raw)) {
            return response('Empty body', 400);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return response('Invalid XML', 400);
        }

        // Register namespaces if present
        $namespaces = $xml->getDocNamespaces(true);

        // find AuditRequest node
        $nodes = $xml->xpath('//iae:AuditRequest');
        if (empty($nodes)) {
            // fallback: any AuditRequest
            $nodes = $xml->xpath('//AuditRequest');
        }
        if (empty($nodes)) {
            return response('Missing AuditRequest', 400);
        }

        $req = $nodes[0];

        $teamId     = (string) ($req->TeamID       ?? '');
        $activity   = (string) ($req->ActivityName ?? '');
        $logContent = (string) ($req->LogContent   ?? '');

        // jwt payload from middleware
        $jwt = $request->attributes->get('jwt_payload');

        // Map to local user if email present
        if (is_object($jwt) && isset($jwt->email)) {
            $email = $jwt->email;
            $user  = User::firstOrCreate(['email' => $email], ['name' => ($jwt->name ?? $email)]);
        }

        // try to decode logContent if JSON
        $meta    = null;
        $decoded = json_decode($logContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $meta = $decoded;
        }

        // persist
        AuditLog::create([
            'team_id'     => $teamId,
            'activity'    => $activity,
            'log_content' => $logContent,
            'meta'        => $meta ? json_encode($meta) : null,
        ]);

        $receipt = 'IAE-LOG-' . strtoupper(Str::random(8));

        $responseXml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">' .
            '<soap:Body><iae:AuditResponse>' .
            "<iae:Status>SUCCESS</iae:Status>" .
            "<iae:ReceiptNumber>{$receipt}</iae:ReceiptNumber>" .
            '</iae:AuditResponse></soap:Body></soap:Envelope>';

        return response($responseXml, Response::HTTP_OK)->header('Content-Type', 'text/xml');
    }
}
