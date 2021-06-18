<?php

return [
    /*
     * By default all permissions are cached for 24 hours to speed up performance.
     * When permissions or roles are updated the cache is flushed automatically.
     */

    'ttl' => DateInterval::createFromDateString('24 hours'),

    /*
     * The cache key used to store all permissions.
     */

    'permission_key' => 'permission.cache',

    /*
     * The cache key used to store all roles.
     */

    'role_key' => 'role.cache'
];
