# Laravel Firebase Push Notification Service

A Laravel service for sending push notifications to Android and iOS devices using Firebase Cloud Messaging (FCM) via the Kreait Firebase PHP SDK.

---

## Features

- Send push notifications to multiple device tokens (Android/iOS)
- Handles APNs (Apple Push Notification Service) options for iOS
- Logs invalid tokens for cleanup
- Easily extendable for other Firebase features

---

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- [kreait/laravel-firebase](https://github.com/kreait/laravel-firebase) ^6.1
- Firebase project with Service Account credentials

---

## Installation

1. **Install the package via Composer:**

   ```bash
   composer require kreait/laravel-firebase
   ```

2. **Publish the Firebase configuration (optional):**

   ```bash
   php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider" --tag=config
   ```

   This will create `config/firebase.php`.

3. **Obtain Firebase Service Account Key:**

   - Go to [Firebase Console](https://console.firebase.google.com/)
   - Project Settings → Service Accounts → Generate New Private Key
   - Download the JSON file and place it in a secure location (e.g., `storage/app/firebase-service-account.json`)

4. **Configure environment variables:**
   Add the following to your `.env` file:
   ```env
   FIREBASE_CREDENTIALS=/absolute/path/to/storage/app/firebase-service-account.json
   # Optionally, add your database URL if needed:
   # FIREBASE_DATABASE_URL=https://<your-project>.firebaseio.com
   ```

---

## Usage

### Service Location

The main service class is located at:

```
app/Services/FirebaseService.php
```

### Example: Sending a Single Notification

```php
use App\Services\FirebaseService;

$firebaseService = new FirebaseService();

$deviceToken = 'your_device_token_here';
$title = 'Hello!';
$body = 'This is a test notification.';
$data = [
    'key1' => 'value1',
    // ...
];

$result = $firebaseService->sendSingleNotification($deviceToken, $title, $body, $data);

if ($result['success']) {
    // Notification sent successfully
} else {
    // Handle error
    if (isset($result['invalid_tokens'])) {
        // Handle invalid tokens
    }
    if (isset($result['error'])) {
        // Log or display the error message
    }
}
```

### Example: Sending a Multicast Notification

```php
use App\Services\FirebaseService;

$firebaseService = new FirebaseService();

$deviceTokens = [
    'token_1',
    'token_2',
    // ...
];
$title = 'Hello!';
$body = 'This is a test notification.';
$data = [
    'key1' => 'value1',
    // ...
];

$report = $firebaseService->sendMulticastNotification($deviceTokens, $title, $body, $data);

if ($report->hasFailures()) {
    // Handle invalid tokens or failures
}
```

#### Method Reference

- `sendSingleNotification(string $deviceToken, string $title, string $body, array $data = [])`

  - Sends a notification to a single device token.
  - Returns a structured array with `success`, `report`, `invalid_tokens`, `failures`, or `error` keys.
  - Handles and logs errors and invalid tokens using Laravel's logger.

- `sendMulticastNotification(array $deviceTokens, string $title, string $body, array $data = [])`
  - Sends a notification to multiple device tokens.
  - Handles APNs options for iOS.
  - Logs invalid tokens using Laravel's logger.

---

## Error Handling

The `sendSingleNotification` method provides robust error handling:

- Catches and logs exceptions from the Firebase SDK.
- Checks the send report for failures and logs invalid tokens.
- Returns a structured array for easy error handling in your application.

**Example return values:**

- On success:
  ```php
  [
      'success' => true,
      'report' => $report,
  ]
  ```
- On failure (invalid token):
  ```php
  [
      'success' => false,
      'invalid_tokens' => [...],
      'failures' => [...],
  ]
  ```
- On exception:
  ```php
  [
      'success' => false,
      'error' => 'Exception message',
  ]
  ```

---

## Troubleshooting

- **Invalid Token Errors:**
  - Invalid tokens are logged via Laravel's logger. Check your logs for details. The `sendSingleNotification` method also returns invalid tokens in its result array.
- **Credentials Issues:**
  - Ensure the `FIREBASE_CREDENTIALS` path in `.env` is correct and readable by your app.
- **APNs/FCM Delivery:**
  - Make sure your Firebase project is configured for both Android and iOS.
- **General Errors:**
  - All exceptions and failures are logged. Check your Laravel logs for details if a notification fails to send.

---

## References

- [Kreait Laravel Firebase Documentation](https://github.com/kreait/laravel-firebase)
- [Firebase Admin PHP SDK](https://firebase-php.readthedocs.io/en/stable/)
