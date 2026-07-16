<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contact form recipient
    |--------------------------------------------------------------------------
    |
    | Address that receives contact-form submissions (bug reports, feature
    | suggestions, general messages). Falls back to the app's "from" address
    | so the form works even without a dedicated env var configured.
    |
    */
    'recipient' => env('CONTACT_RECIPIENT_EMAIL', env('MAIL_FROM_ADDRESS', 'hiba.sabouh.98@gmail.com')),
];
