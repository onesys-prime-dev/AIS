<?php

if (!function_exists('user_id')) {
    function user_id()
    {
        $session = session();
        return $session->get('user_id') ?? 0;
    }
}