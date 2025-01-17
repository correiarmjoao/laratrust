<?php

declare(strict_types=1);

namespace Laratrust\Checkers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\Role\RoleChecker;
use Laratrust\Checkers\Role\RoleDefaultChecker;
use Laratrust\Checkers\Role\RoleQueryChecker;
use Laratrust\Checkers\User\UserChecker;
use Laratrust\Checkers\User\UserDefaultChecker;
use Laratrust\Checkers\User\UserQueryChecker;
use Laratrust\Checkers\Group\GroupChecker;
use Laratrust\Checkers\Group\GroupDefaultChecker;
use Laratrust\Checkers\Group\GroupQueryChecker;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Role;
use Laratrust\Contracts\Group;

class CheckersManager
{
    public function __construct(protected LaratrustUser|Role|Group|Model $model)
    {
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getUserChecker(): UserChecker
    {
        $checker = Config::get('laratrust.checkers.user', Config::get('laratrust.checker', 'default'));

        switch ($checker) {
            case 'default':
                return new UserDefaultChecker($this->model);
            case 'query':
                return new UserQueryChecker($this->model);
            default:
                if (!is_a($checker, UserChecker::class, true)) {
                    throw new \RuntimeException('User checker must extend UserChecker');
                }

                return app()->make($checker, ['user' => $this->model]);
        }
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getRoleChecker(): RoleChecker
    {
        $checker = Config::get('laratrust.checkers.role', Config::get('laratrust.checker', 'default'));

        switch ($checker) {
            case 'default':
                return new RoleDefaultChecker($this->model);
            case 'query':
                return new RoleQueryChecker($this->model);
            default:
                if (!is_a($checker, RoleChecker::class, true)) {
                    throw new \RuntimeException('Role checker must extend RoleChecker');
                }

                return app()->make($checker, ['role' => $this->model]);
        }
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getGroupChecker(): GroupChecker
    {
        $checker = Config::get('laratrust.checkers.group', Config::get('laratrust.checker', 'default'));

        switch ($checker) {
            case 'default':
                return new GroupDefaultChecker($this->model);
            case 'query':
                return new GroupQueryChecker($this->model);
            default:
                if (!is_a($checker, GroupChecker::class, true)) {
                    throw new \RuntimeException('Group checker must extend GroupChecker');
                }

                return app()->make($checker, ['group' => $this->model]);
        }
    }
}
