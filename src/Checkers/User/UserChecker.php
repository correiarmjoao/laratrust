<?php

declare(strict_types=1);

namespace Laratrust\Checkers\User;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Helper;

abstract class UserChecker
{
    public function __construct(protected LaratrustUser|Model $user)
    {
    }

    /**
     * Get the user roles.
     */
    abstract public function getCurrentUserRoles(): array;

    /**
     * Get the user groups.
     */
    abstract public function getCurrentUserGroups(): array;

    /**
     * Get the user permissions.
     */
    abstract public function getCurrentUserPermissions(): array;

    /**
     * Resolve user permissions.
     */
    abstract public function resolvePermissions(): array;

    /**
     * Checks if the user has a role by its name.
     */
    abstract public function currentUserHasRole(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool;

    /**
     * Checks if the user is in a group by its name.
     */
    abstract public function currentUserHasGroup(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     */
    abstract public function currentUserHasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool;

    abstract public function currentUserFlushCache(bool $recreate = true);

    /**
     * Checks role(s) and permission(s).
     *
     * @param  array  $options  validate_all (true|false) or return_type (boolean|array|both)
     *
     * @throws \InvalidArgumentException
     */
    public function currentUserHasAbility(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        array $options = []
    ): array|bool {
        // Convert string to array if that's what is passed in.
        $roles = Helper::standardize($roles, true);
        $permissions = Helper::standardize($permissions, true);

        // Setup default values and validate options.
        $options = $this->validateAndSetOptions($options, [
            'validate_all' => [
                'available' => [false, true],
                'default' => false,
            ],
            'return_type' => [
                'available' => ['boolean', 'array', 'both'],
                'default' => 'boolean',
            ],
        ]);

        if ($options['return_type'] == 'boolean') {
            $hasRoles = $this->currentUserHasRole($roles, $options['validate_all']);
            $hasPermissions = $this->currentUserHasPermission($permissions, $options['validate_all']);

            return $options['validate_all']
                ? $hasRoles && $hasPermissions
                : $hasRoles || $hasPermissions;
        }

        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->currentUserHasRole($role);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->currentUserHasPermission($permission);
        }

        // If validate all and there is a false in either.
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if (($options['validate_all'] && !(in_array(false, $checkedRoles) || in_array(false, $checkedPermissions))) || (!$options['validate_all'] && (in_array(true, $checkedRoles) || in_array(true, $checkedPermissions)))) {
            $validateAll = true;
        } else {
            $validateAll = false;
        }

        // Return based on option.
        if ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions];
        }

        return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]];
    }

    /**
     * Checks if the option exists inside the array,
     * otherwise, it sets the first option inside the default values array.
     */
    private function validateAndSetOptions(array $options, array $toVerify): array
    {
        foreach ($toVerify as $option => $config) {
            if (!isset($options[$option])) {
                $options[$option] = $config['default'];

                continue;
            }

            if (!in_array($options[$option], $config['available'], true)) {
                throw new InvalidArgumentException();
            }
        }

        return $options;
    }
}
