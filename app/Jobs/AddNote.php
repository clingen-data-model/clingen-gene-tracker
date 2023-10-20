<?php

namespace App\Jobs;

use App\Contracts\Notable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;

class AddNote
{
    use Dispatchable;

    protected $subject;

    protected $content;

    protected $topic;

    protected $author;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notable $subject, string $content, string $topic = null, model $author = null)
    {
        //
        $this->subject = $subject;
        $this->content = $content;
        $this->topic = $topic;
        $this->author = $author ?? Auth::user();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->subject
            ->notes()
            ->create([
                'content' => $this->content,
                'topic' => $this->topic,
                'author_type' => $this->author ? $this->author::class : null,
                'author_id' => $this->author ? $this->author->id : null,
            ]);
    }
}
