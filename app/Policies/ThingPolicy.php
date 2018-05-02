<?php

namespace App\Policies;

use App\Project;
use App\User;
use App\Thing;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class ThingPolicy
{
    use HandlesAuthorization;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Determine whether the user can view the thing.
     *
     * @param  \App\User $user
     * @param  \App\Thing $thing
     * @return mixed
     */
    public function view(User $user, Thing $thing)
    {
        return $this->isOwner($user, $thing);
    }

    /**
     * Determine whether the user can create things.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        $project = Project::where('_id', $this->request->get('project_id'))->first();
        return $project && $project['owner']['_id'] == $user['_id'];
    }

    /**
     * Determine whether the user can update the thing.
     *
     * @param  \App\User $user
     * @param  \App\Thing $thing
     * @return mixed
     */
    public function update(User $user, Thing $thing)
    {
        return $this->isOwner($user, $thing);
    }

    /**
     * Determine whether the user can delete the thing.
     *
     * @param  \App\User $user
     * @param  \App\Thing $thing
     * @return mixed
     */
    public function delete(User $user, Thing $thing)
    {
        return $this->isOwner($user, $thing);
    }


    /**
     * Determine whether the user is the owner of the thing.
     *
     * @param  \App\User $user
     * @param Thing $thing
     * @return mixed
     */
    private function isOwner(User $user, Thing $thing)
    {
        return $thing['owner']['_id'] == $user['id'];
    }
}
