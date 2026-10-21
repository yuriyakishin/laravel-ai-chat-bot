<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

enum ChatMode: string
{
    case AI = 'ai';
    case HYBRID = 'hybrid';
    case HUMAN = 'human';
}
