<?php

use Illuminate\Support\Carbon;
use Yu\AiChatBot\Models\Conversation;

it('keeps an old conversation that received a recent message and prunes an inactive one', function () {
    config(['ai-chat.prune_after_days' => 30]);

    Carbon::setTestNow(now()->subDays(40));
    $active = Conversation::create(['uuid' => 'conv-active', 'owner_type' => 'guest', 'owner_id' => 'visitor-1']);
    $inactive = Conversation::create(['uuid' => 'conv-inactive', 'owner_type' => 'guest', 'owner_id' => 'visitor-2']);
    Carbon::setTestNow();

    $active->messages()->create(['role' => 'user', 'content' => 'still here']);

    $this->artisan('ai-chat:prune')->assertSuccessful();

    expect(Conversation::whereKey($active->id)->exists())->toBeTrue()
        ->and(Conversation::whereKey($inactive->id)->exists())->toBeFalse();
});

it('does not delete anything when prune_after_days is zero', function () {
    config(['ai-chat.prune_after_days' => 0]);

    Conversation::create(['uuid' => 'conv-1', 'owner_type' => 'guest', 'owner_id' => 'visitor-1']);

    $this->artisan('ai-chat:prune')->assertSuccessful();

    expect(Conversation::count())->toBe(1);
});
