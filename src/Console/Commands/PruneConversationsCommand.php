<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Console\Commands;

use Illuminate\Console\Command;
use Yu\AiChatBot\Models\Conversation;

class PruneConversationsCommand extends Command
{
    protected $signature = 'ai-chat:prune';

    protected $description = 'Delete conversations inactive for longer than ai-chat.prune_after_days';

    /**
     * @return int
     */
    public function handle(): int
    {
        $days = config('ai-chat.prune_after_days');
        $count = Conversation::where('updated_at', '<', now()->subDays($days))->delete();
        $this->info("Pruned {$count} conversation(s).");
        return self::SUCCESS;
    }
}
