<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invite lifetime
    |--------------------------------------------------------------------------
    |
    | How long an invite link can be used to join a context. The link stops
    | working after this, and the pending invite is removed from the list.
    |
    */

    'lifetime_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Password reset link lifetime
    |--------------------------------------------------------------------------
    |
    | How long a link for choosing a new password works after an admin creates
    | it. Shorter than an invite, since it opens an existing account.
    |
    */

    'password_reset_lifetime_hours' => 24,

];
