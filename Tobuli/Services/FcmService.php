<?php

namespace Tobuli\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Tobuli\Entities\FcmToken;
use Tobuli\Entities\FcmTokenableInterface;
use Tobuli\Helpers\FirebaseConfig;

class FcmService
{
    private FirebaseConfig $firebaseConfig;
    private Client $client;
    private string $firebaseUrl;

    public function __construct()
    {
        $this->firebaseConfig = new FirebaseConfig();
    }

    public function setFcmToken(FcmTokenableInterface $tokenable, string $fcmToken)
    {
        $token = $tokenable->fcmTokens()->firstOrNew(['token' => $fcmToken]);
        $token->save();
    }

    /**
     * @param Model&FcmTokenableInterface $tokenable
     */
    public function send($tokenable, $title, $body, array $data = [])
    {
        if (!$tokenable instanceof FcmTokenableInterface) {
            return;
        }

        $tokens = $tokenable->fcmTokens->pluck('token')->toArray();

        if (!$tokens) {
            return;
        }

        $payload = array_merge($data, ['title' => $title, 'content' => $body]);

        $this->sendToTokens($tokens, $title, $body, $payload);
    }

    public function sendToTokens(array $tokens, string $title, string $body, ?array $payloadData = null): void
    {
        $message = $this->buildMessage($title, $body, $payloadData);

        if ($this->firebaseConfig->isCustomConfig()) {
            $this->sendDirect($tokens, $message);
            return;
        }

        // user has custom config but did not upload firebase-config.json yet
        if (config('fcm.http.sender_id')) {
            return;
        }

        $this->sendViaBridge($tokens, $message);
    }

    private function sendDirect(array $tokens, array $message): void
    {
        foreach ($tokens as $token) {
            $message['message']['token'] = $token;

            try {
                $this->getClient()->post($this->getFirebaseUrl(), [
                    RequestOptions::JSON => $message,
                    RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $this->firebaseConfig->getAccessToken()],
                ]);
            } catch (ClientException $exception) {
                $data = json_decode($exception->getResponse()->getBody()->getContents(), true) ?? [];

                $success = $this->handleSendError($token, $exception->getCode(), $data);

                if (!$success) {
                    throw $exception;
                }
            }
        }
    }

    private function sendViaBridge(array $tokens, array $message): void
    {
        foreach ($tokens as $token) {
            $message['message']['token'] = $token;

            try {
                $this->getClient()->post(config('fcm.http.bridge_url'), [
                    RequestOptions::JSON => $message,
                ])->getBody()->getContents();
            } catch (ClientException $exception) {
                $data = json_decode($exception->getResponse()->getBody()->getContents(), true) ?? [];

                $success = $this->handleSendError($token, $exception->getCode(), $data);

                if (!$success) {
                    throw $exception;
                }
            }
        }
    }

    private function handleSendError(string $token, int $code, $data): bool
    {
        if (!is_array($data)) {
            $data = (array)$data;
        }

        if ($code === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $invalidToken = isset($data['message.token']);

            if ($invalidToken) {
                FcmToken::where('token', $token)->delete();
            }

            return $invalidToken && count($data) === 1;
        }

        if (!isset($data['error']['details'])) {
            return false;
        }

        $data = $data['error']['details'];

        if (!is_array($data)) {
            return false;
        }

        $success = true;
        $msg = $data['message'] ?? '';

        foreach ($data as $details) {
            if (!isset($details['errorCode'])) {
                $success = false;
                continue;
            }

            $code = $details['errorCode'];

            // https://firebase.google.com/docs/reference/fcm/rest/v1/ErrorCode
            switch (true) {
                case $code === 'INVALID_ARGUMENT' && $msg === 'The registration token is not a valid FCM registration token':
                case $code === 'UNREGISTERED':
                case $code === 'SENDER_ID_MISMATCH':
                    FcmToken::where('token', $token)->delete();
                    break;
                default:
                    $success = false;
            }
        }

        return $success;
    }

    private function buildMessage(string $title, string $body, ?array $payloadData = null): array
    {
        $message = [
            'token' => null,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if ($payloadData) {
            $message['data'] = $payloadData;
        }

        if (isset($message['data'])) {
            array_walk($message['data'], function (&$value) {
                if (is_string($value)) {
                    return;
                }

                if (is_scalar($value)) {
                    $value = (string)$value;
                } else {
                    $value = json_encode($value);
                }
            });
        }

        $channelId = config('fcm.channel_id');
        $sound = config('fcm.sound');
        $ttl = 20 * 60; // https://firebase.google.com/docs/cloud-messaging/concept-options#ttl

        $message['android']['ttl'] = $ttl . 's';
        $message['apns']['headers'] = ['apns-expiration' => (string)(time() + $ttl)];

        $android = array_filter([
            'sound' => $sound,
            'channel_id' => $channelId,
        ]);

        if ($android) {
            $message['android']['notification'] = $android;
        }

        $apple = array_filter(['sound' => $sound]);

        if ($apple) {
            $message['apns']['payload'] = ['aps' => $apple];
        }

        return ['message' => $message];
    }

    private function getFirebaseUrl(): string
    {
        if (isset($this->firebaseUrl)) {
            return $this->firebaseUrl;
        }

        $projectId = $this->firebaseConfig->getCustomConfig()['project_id'] ?? '';

        return $this->firebaseUrl = "v1/projects/$projectId/messages:send";
    }

    private function getClient(): Client
    {
        if (isset($this->client)) {
            return $this->client;
        }

        $uri = $this->firebaseConfig->isCustomConfig()
            ? 'https://fcm.googleapis.com'
            : null;

        // client does not handle access token without providing base_uri
        return $this->client = new Client(array_filter(['base_uri' => $uri]));
    }
}
