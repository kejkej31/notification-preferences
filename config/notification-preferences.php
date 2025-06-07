<?php

return [
    /*
    | Register your notification classes which user should be able to set preferences for here. 
    | 
    | The key should be a unique string identifier for the notification type
    | (e.g., 'PostCreated', 'CommentReplied'), and the value should be the
    | fully qualified class name of the notification.
    */
    'notifications' => [
        // 'PostCreated' => App\Notifications\PostCreated::class,
    ],

    'channels' => [
        'mail',
        'database',
        // Other channels/routes
    ],
];
