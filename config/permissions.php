<?php

/*
|--------------------------------------------------------------------------
| Application permissions
|--------------------------------------------------------------------------
|
| Every permission in the system, keyed by its name. The PermissionSeeder
| creates these and grants them to the Administrator role. Permissions are
| named "<module>.<action>" and checked through Laravel's Gate.
|
*/

return [
    'settings.manage' => 'Manage school settings',
];
