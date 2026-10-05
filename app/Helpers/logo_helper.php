<?php

if (!function_exists('get_logo_url')) {
    /**
     * Returns the URL of the company logo, or null if none exists.
     * Adds a timestamp query parameter to force browser refresh when the file changes.
     *
     * @return string|null
     */
    function get_logo_url()
    {
        $path = FCPATH . 'uploads/logo/';
        // Look for any file starting with "company_logo." (e.g. company_logo.png, company_logo.svg)
        $files = glob($path . 'company_logo.*');
        if (!empty($files)) {
            $file = $files[0]; // use the first match
            return base_url('uploads/logo/' . basename($file)) . '?v=' . filemtime($file);
        }
        return null;
    }
}