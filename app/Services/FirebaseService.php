<?php
namespace App\Services;

use Throwable;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseService
{
    protected $messaging, $firebase;

    public function __construct()
    {
        $this->firebase = (new Factory)
            ->withServiceAccount(storage_path(config('services.firebase.credentials')));

        // Initialize the messaging object once during the class instantiation
        $this->messaging = $this->firebase->createMessaging();
    }

    public function sendPushNotificationToToken(string $token, string $title, string $body, array $data = [], string $deviceType = 'Android')
    {
        // Check if token, title, and body are valid
        if (empty($token) || empty($title) || empty($body)) {
            Log::error('Firebase push notification invalid data', ['token' => $token, 'title' => $title, 'body' => $body]);
            return false;
        }

        try {
            // Build the message
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data, // Custom data payload
            ]);

            // Log the message before sending it
            // Log::info('Sending Firebase notification:', ['message' => json_encode($message)]);

            // Send the message
            return $this->messaging->send($message);
        } catch (Throwable $e) {
            // Log the error and return false
            Log::error('Error sending Firebase push notification', [
                'error' => $e->getMessage(),
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            return false;
        }
    }

    public function sendPushNotificationToTopic(string $topic, string $title, string $body, array $data = [])
    {
        // Build the message
        $message = CloudMessage::fromArray([
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data, // Custom data payload
        ]);

        // Log the message before sending it
        Log::info('Sending Firebase topic notification:', ['message' => json_encode($message)]);

        // Send the message
        return $this->messaging->send($message);
    }
}

 