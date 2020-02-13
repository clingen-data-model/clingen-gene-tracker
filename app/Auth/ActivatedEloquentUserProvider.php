<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;

class ActivatedEloquentUserProvider extends EloquentUserProvider implements UserProvider
{
    // /**
    //  * Retrieve a user by their unique identifier.
    //  *
    //  * @param  mixed  $identifier
    //  * @return \Illuminate\Contracts\Auth\Authenticatable|null
    //  */
    // public function retrieveById($identifier) {
    //     $retrievedModel = parent::retrieveById($identifier);
    //     return is_null($retrievedModel->deactivated_at) ? $retrievedModel : null;
    // }

    // /**
    //  * Retrieve a user by their unique identifier and "remember me" token.
    //  *
    //  * @param  mixed  $identifier
    //  * @param  string  $token
    //  * @return \Illuminate\Contracts\Auth\Authenticatable|null
    //  */
    // public function retrieveByToken($identifier, $token) {
    //     $retrievedModel = parent::retrieveByToken($identifier, $token);
    //     return is_null($retrievedModel->deactivated_at) ? $retrievedModel : null;
    // }

    // /**
    //  * Retrieve a user by the given credentials.
    //  *
    //  * @param  array  $credentials
    //  * @return \Illuminate\Contracts\Auth\Authenticatable|null
    //  */
    // public function retrieveByCredentials(array $credentials) 
    // {
    //     $retrievedModel = parent::retrieveByCredentials($credientials);
    //     return is_null($retrievedModel->deactivated_at) ? $retrievedModel : null;
    // }

    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->whereNull('deactivated_at');
    }

}