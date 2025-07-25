<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        // Use environment variable or fallback path for service account
        $serviceAccountPath = getenv('FIREBASE_CREDENTIALS') ?: __DIR__ . '/../../public/androidFcmFile.json';
        $factory = (new Factory)
            ->withServiceAccount($serviceAccountPath);

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

    /**
     * Send a notification to a single device token.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendSingleNotification(string $deviceToken, string $title, string $body, array $data = [])
    {
        try {
            $report = $this->sendMulticastNotification([$deviceToken], $title, $body, $data);

            if ($report->hasFailures()) {
                $failures = $report->failures()->getItems();
                $invalidTokens = [];
                foreach ($failures as $failure) {
                    $invalidTokens[] = $failure->target()->value();
                    Log::error('FCM send failure', [
                        'token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                    ]);
                }
                return [
                    'success' => false,
                    'invalid_tokens' => $invalidTokens,
                    'failures' => $failures,
                ];
            }

            return [
                'success' => true,
                'report' => $report,
            ];
        } catch (MessagingException $e) {
            Log::error('FCM MessagingException', [
                'token' => $deviceToken,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('FCM General Exception', [
                'token' => $deviceToken,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
