<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        // Initialize Firebase with credentials
        $factory = (new Factory)
            ->withServiceAccount(public_path('androidFcmFile.json'));

        $this->messaging = $factory->createMessaging();
    }

    public function sendMulticastNotification(array $deviceTokens, string $title, string $body, array $data = [])
    {
        // Create a notification message
        $message = CloudMessage::new()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData($data);

        $apnsOptions = [
            'apns' => [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default', // Optional: specify sound
                        'badge' => 1, // Optional: specify badge count
                    ],
                ],
            ],
        ];
        $message = $message->withApnsConfig($apnsOptions);

        // Send the message to multiple device tokens
        $sendReport = $this->messaging->sendMulticast($message, $deviceTokens);

        // Handle failures (invalid tokens)
        if ($sendReport->hasFailures()) {
            $invalidTokens = [];

            foreach ($sendReport->failures()->getItems() as $failure) {
                $invalidTokens[] = $failure->target()->value();
            }

            // Log or remove invalid tokens from the database
            // Example: Log the invalid tokens
            Log::warning('Invalid FCM tokens:', $invalidTokens);
        }

        return $sendReport;
    }
}
