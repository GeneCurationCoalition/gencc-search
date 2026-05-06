<?php

namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Wildcard assumes the app is only reachable through the load balancer.
     * Narrow to the LB's CIDR if the app could ever be addressed directly,
     * otherwise X-Forwarded-* can be spoofed (bypassing the per-IP download
     * quota in DownloadController).
     *
     * @var array|string
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * HEADER_X_FORWARDED_ALL so request()->ip(), getScheme(), and getHost()
     * reflect the original client behind the HTTPS-terminating LB. ip() is
     * the per-IP download quota key; scheme/host drive absolute URL generation.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
