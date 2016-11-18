<?php
add_action('wp_logout', 'destroySession');
add_action('wp_login', 'destroySession');

function destroySession()
{
    if (! isset($_SESSION)) {
        @session_start();
    }
    @session_destroy();
}
