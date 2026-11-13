    private function insertRoboCallFeedback($user, $event, $alert, $status, $responseBody, $callId = null, $errorCode = null, $errorMessage = null)
    {
        DB::table('hypegps_web.robo_calls')->insertGetId([
            'user_id' => $user, 'alert_id' => $alert, 'alert_name' => "FeedBack Call", 'call_to' => $callId,
            'status' => $status, 'device_name' => $event, 'is_call_send' => ($status === 'ringing') ? 1 : 0,
            'rc_id' => $callId, 'rc_voice_id' => 35, 'error_code' => $errorCode, 'error_message' => $errorMessage,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertRoboCall($user, $event, $alert, $status, $responseBody, $callerId = null, $errorCode = null, $errorMessage = null, $timeAgo = null, $voiceId = null, $is_call_send = 0, $robocallId = null, $decoded = null)
    {
        $signature = md5(($event['device_id'] ?? '') . ($event['alert_id'] ?? '') . ($event['name'] ?? ''));
        try {
            DB::transaction(function () use ($user, $event, $alert, $status, $callerId, $errorCode, $errorMessage, $timeAgo, $voiceId, $is_call_send, $robocallId, $decoded, $signature) {
                DB::table('hypegps_web.robo_calls')->insert([
                    'alert_signature'  => $signature, 'user_id'          => $user->id,
                    'device_id'        => $event['device_id'], 'event_id'         => $event['id'],
                    'device_name'      => $event['device_name'], 'alert_id'         => $alert->id,
                    'alert_name'       => $alert->name, 'type'             => $event['type'],
                    'time'             => $event['time'], 'call_to'          => $callerId,
                    'status'           => $status, 'name'             => $event['name'],
                    'detail'           => $event['detail'], 'is_call_send'     => $is_call_send,
                    'rc_id'            => $robocallId, 'rc_voice_id'      => $voiceId,
                    'error_code'       => $errorCode, 'error_message'    => $errorMessage,
                    'decoded_response' => $decoded, 'retry_time'       => $timeAgo,
                    'created_at'       => now(),
                ]);
            });
        } catch (\Exception $e) {
            \Log::error("DB Transaction failed: " . $e->getMessage());
        }
    }
