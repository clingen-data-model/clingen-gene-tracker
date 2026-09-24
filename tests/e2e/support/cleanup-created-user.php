<?php

// Called only by the disposable Admin User creation browser test.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (!app()->environment('testing') || \Illuminate\Support\Facades\DB::selectOne('select database() as name')->name !== 'genetracker_e2e') {
    throw new RuntimeException('Refusing User fixture cleanup outside genetracker_e2e.');
}

\Illuminate\Support\Facades\DB::transaction(function () {
    $user = \App\User::withTrashed()->where('email', 'e2e-created-user@example.com')->first();
    if (!$user) {
        return;
    }
    $user->expertPanels()->detach();
    $user->affiliations()->detach();
    $user->roles()->detach();
    $user->permissions()->detach();
    $user->forceDelete();
    \Illuminate\Support\Facades\DB::table('revisions')->where('revisionable_type', \App\User::class)->where('revisionable_id', $user->id)->delete();
});
