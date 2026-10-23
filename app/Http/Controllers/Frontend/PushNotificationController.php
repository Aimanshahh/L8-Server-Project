<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationController extends Controller
{
    public function index()
    {
        return view('Frontend.PushNotification.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $tokens = DB::table('device_tokens')->pluck('token')->toArray();

        if (empty($tokens)) {
            return back()->with('error', 'No mobile devices registered yet.');
        }

        try {
            $credentialsPath = env('FIREBASE_CREDENTIALS');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Firebase credentials file not found at: " . $credentialsPath);
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $credentials['project_id'];

            // 1. Get the OAuth 2.0 Access Token using the Service Account JSON
            $accessToken = $this->getAccessToken($credentials);

            $successCount = 0;
            $failureCount = 0;

            // 2. Loop through tokens and send via cURL
            foreach ($tokens as $token) {
                $response = $this->sendFcmMessage($accessToken, $projectId, $token, $request->title, $request->body);
                
                if ($response === true) {
                    $successCount++;
                } else {
                    $failureCount++;
                    \Log::error('FCM Error: ' . $response);
                }
            }

            return back()->with('success', "Sent successfully to {$successCount} devices. Failed: {$failureCount}.");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error sending notification: ' . $e->getMessage());
        }
    }

    // 🔥 HELPER: Generate JWT and get Google OAuth Token
    private function getAccessToken($credentials)
    {
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $credentials['private_key'], 'sha256');
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \Exception("cURL Error (OAuth): " . $err);
        }

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }

        throw new \Exception('Failed to get Google Access Token. Response: ' . $response);
    }

    // 🔥 HELPER: Send single message to HTTP v1 API
    private function sendFcmMessage($accessToken, $projectId, $token, $title, $body)
    {
        $payload = json_encode([
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return "cURL Error (FCM): " . $err;
        }

        if ($httpCode === 200) {
            return true;
        }

        return $result; // Returns the error message from Google
    }
}