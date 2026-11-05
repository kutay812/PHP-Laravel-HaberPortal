<?php

declare(strict_types=1);

return [
    'rate_limits' => [
        'rss_fetch_per_minute'     => (int) env('RATE_LIMIT_RSS_FETCH_PER_MIN', 2),
        'news_write_per_minute'    => (int) env('RATE_LIMIT_NEWS_WRITE_PER_MIN', 10),
        'comment_write_per_minute' => (int) env('RATE_LIMIT_COMMENT_PER_MIN', 5),
        'profile_write_per_minute' => (int) env('RATE_LIMIT_PROFILE_PER_MIN', 6),
        'public_browse_per_minute' => (int) env('RATE_LIMIT_PUBLIC_BROWSE_PER_MIN', 90),
        'public_search_per_minute' => (int) env('RATE_LIMIT_PUBLIC_SEARCH_PER_MIN', 15),
        'admin_search_per_minute'  => (int) env('RATE_LIMIT_ADMIN_SEARCH_PER_MIN', 30),
        'xml_feeds_per_minute'     => (int) env('RATE_LIMIT_XML_FEEDS_PER_MIN', 20),
        'api_per_minute'           => (int) env('RATE_LIMIT_API_PER_MIN', 60),
    ],

    'daily_quotas' => [
        'rss_fetch' => [
            'per_actor' => (int) env('DAILY_USER_RSS_FETCH_QUOTA', 24),
            'global'    => (int) env('DAILY_RSS_FETCH_QUOTA', 100),
        ],
        'news_write' => [
            'per_actor' => (int) env('DAILY_NEWS_WRITE_QUOTA', 100),
            'global'    => 0,
        ],
        'comments_write' => [
            'per_actor' => (int) env('DAILY_COMMENT_QUOTA', 30),
            'global'    => 0,
        ],
        'profile_write' => [
            'per_actor' => (int) env('DAILY_PROFILE_WRITE_QUOTA', 25),
            'global'    => 0,
        ],
        'public_search' => [
            'per_actor' => (int) env('DAILY_SEARCH_QUOTA', 300),
            'global'    => 0,
        ],
    ],
];

