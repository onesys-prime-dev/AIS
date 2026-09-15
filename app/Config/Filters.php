<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Shield\Filters\SessionAuth;
use CodeIgniter\Shield\Filters\TokenAuth;
use CodeIgniter\Shield\Filters\ChainAuth;
use App\Filters\FaceVerified;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'session'       => SessionAuth::class,
        'tokens'        => TokenAuth::class,
        'auth'          => [ChainAuth::class, \App\Filters\PortalAccessFilter::class],
        'portalaccess' => \App\Filters\PortalAccessFilter::class,
        'faceverified'  => FaceVerified::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // CodeIgniter's Filters::processGlobals() only honors an "except"
            // list when the entry itself is this array shape — a bare 'csrf'
            // string here (as this used to be) is always kept regardless of
            // the separate $csrf property below, which the framework never
            // reads at all. That silently broke this exemption for every
            // request: sendPdf/compute/send's JS doesn't send a CSRF token,
            // so every call was hitting a real (and pointless, since it can
            // never succeed) CSRF check and failing with a 403.
            'csrf' => ['except' => [
                'client_portal/payroll/compute',
                'client_portal/payroll/sendPdf',
                'client_portal/payroll/send/*',
                'client_portal/payroll/savePayslipRecord',
                'client_portal/payroll/computeYearEnd',
                // navigator.sendBeacon() (presence.js's beforeunload handler,
                // firing on every page navigation) cannot set custom headers,
                // so its CSRF token can only travel as a URL query param the
                // server-side CSRF check never reads — structurally
                // impossible to pass otherwise. By far the single largest
                // source of CRITICAL log noise before this was added (99
                // failures in one day, dwarfing every other route combined).
                // Low-sensitivity payload (Online/Away/Offline status only).
                'client_portal/presence/heartbeat',
            ]],
            // 'honeypot',
            // 'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    public array $filters = [
        'portalaccess' => ['before' => ['register']],
        'faceverified' => [
            'before' => [
                'client_portal',
                'client_portal/*',
            ],
        ],
    ];
}
