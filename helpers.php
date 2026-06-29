<?php

use Carbon\Carbon;

function formatDate(string $date)
{
    return $date ? Carbon::parse($date)->format('Y-m-d') : null;
}

function truncate(string|null $text, int $limit = 200): string
{
    $text ??= '';
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . '...';
}