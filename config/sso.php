<?php

return [
    // Master switch for the game single sign-on and stats API.
    'enabled' => (bool) env('SSO_ENABLED', true),

    // How long an authorization code stays valid after /sso/authorize.
    'code_ttl_minutes' => (int) env('SSO_CODE_TTL_MINUTES', 5),

    // How long a player token issued to a game stays valid.
    'token_ttl_days' => (int) env('SSO_TOKEN_TTL_DAYS', 30),

    // Event names that count as one completed play for the stats aggregate.
    'play_events' => ['game_over', 'finish', 'complete', 'round_end', 'session_end'],

    // Default and maximum rows returned by leaderboards.
    'leaderboard_limit' => 25,
    'leaderboard_max' => 100,

    // Maximum events accepted in one batched POST.
    'max_batch' => 50,
];
