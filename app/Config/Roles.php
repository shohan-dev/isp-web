<?php

namespace Config;

/**
 * Canonical role string constants (Item 1 rename — 2026-07).
 */
class Roles
{
    /** Platform / SaaS owner (magic user id 2). */
    public const PLATFORM = 'super_admin';

    /** Tenant ISP owner (registered subscriber). */
    public const TENANT = 'admin';
}
