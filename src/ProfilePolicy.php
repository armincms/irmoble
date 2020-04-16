<?php

namespace Armincms\Irmoble;
 
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Auth\Access\HandlesAuthorization; 
class ProfilePolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any Users.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the User.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Armincms\Irmoble\User  $resource
     * @return mixed
     */
    public function view(User $user, User $resource)
    {
        return true;
    }

    /**
     * Determine whether the user can create Users.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the User.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Armincms\Irmoble\User  $resource
     * @return mixed
     */
    public function update(User $user, User $resource)
    { 
        return $user->is($resource);
    }

    /**
     * Determine whether the user can delete the User.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Armincms\Irmoble\User  $resource
     * @return mixed
     */
    public function delete(User $user, User $resource)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the User.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Armincms\Irmoble\User  $resource
     * @return mixed
     */
    public function restore(User $user, User $resource)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the User.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Armincms\Irmoble\User  $resource
     * @return mixed
     */
    public function forceDelete(User $user, User $resource)
    {
        return false;
    }
}
