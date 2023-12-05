<?php

namespace Tests\Feature\Console\Commands;

use App\Notifications\Curations\PhenotypeOmimEntryRemoved;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeleteDuplicateNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private $u1;

    private $u2;

    private $notifications;

    public function setup(): void
    {
        parent::setup();
        $this->u1 = factory(User::class)->create();
        $this->u2 = factory(User::class)->create();

        $this->notifications = collect();
        foreach ([$this->u1, $this->u2] as $user) {
            $this->notifications->push(factory(DatabaseNotification::class, 10)->create([
                'notifiable_id' => $user->id,
                'notifiable_type' => get_class($user),
                'data' => [
                    'curation' => ['id' => 1],
                    'phenotype' => ['id' => 1],
                ],
            ]));
            $this->notifications->push(factory(DatabaseNotification::class, 2)->create([
                'notifiable_id' => $user->id,
                'notifiable_type' => get_class($user),
                'type' => PhenotypeOmimEntryRemoved::class,
                'data' => [
                    'curation' => ['id' => 1],
                    'phenotype' => ['id' => 1],
                ],
            ]));
            $this->notifications->push(factory(DatabaseNotification::class)->create(
                [
                    'notifiable_id' => $user->id,
                    'notifiable_type' => get_class($user),
                    'data' => [
                        'curation' => ['id' => 2],
                        'phenotype' => ['id' => 1],
                    ],
                ]
            ));
        }
        $this->notifications = $this->notifications->flatten();
    }

    /**
     * @test
     */
    public function test_sets_up_expected_notifications(): void
    {
        $this->assertEquals(26, $this->notifications->count());
    }

    /**
     * @test
     */
    public function it_deletes_duplicates_and_keeps_one_of_each(): void
    {
        Artisan::call('notifications:delete-duplicates --chunk-size=5');

        $this->assertEquals(6, DatabaseNotification::count());
        $this->assertEquals(3, DatabaseNotification::where('notifiable_id', $this->u1->id)->count());
        $this->assertEquals(3, DatabaseNotification::where('notifiable_id', $this->u2->id)->count());
    }
}
