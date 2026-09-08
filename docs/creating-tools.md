# Creating tools

The chat can call your own PHP code while answering a customer - for example, to look up an order status, check stock, or hand the conversation off to a human. This is done via **tools**: small classes that implement `Yu\AiChatBot\Contracts\ToolInterface` and get exposed to the LLM as callable functions.

## The `ToolInterface` contract

```php
interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    public function parameters(): array;

    public function execute(array $arguments): array;
}
```

- **`name()`** - a unique, machine-readable identifier (e.g. `check_order_status`). This is what the LLM calls.
- **`description()`** - tells the LLM *what the tool does and when to use it*. Be explicit about edge cases here - this is effectively an instruction to the model, not just a comment for humans. See `SwitchToHumanChatTool::description()` for an example that also tells the model how to react to a failure result.
- **`parameters()`** - a [JSON Schema](https://json-schema.org/) object describing the arguments the LLM should pass. This is sent to the LLM provider as-is.
- **`execute(array $arguments)`** - runs your logic. `$arguments` is the decoded JSON the LLM sent, matching your `parameters()` schema. The returned array is JSON-encoded and fed back to the LLM as the tool's result - so return only what the model needs to answer the customer, not full model objects.

## A minimal example

```php
namespace App\AiChatTools;

use Yu\AiChatBot\Contracts\ToolInterface;

class CheckOrderStatusTool implements ToolInterface
{
    public function name(): string
    {
        return 'check_order_status';
    }

    public function description(): string
    {
        return 'Looks up the current status of a customer order by its order number.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_number' => [
                    'type' => 'string',
                    'description' => 'The order number as given by the customer.',
                ],
            ],
            'required' => ['order_number'],
        ];
    }

    public function execute(array $arguments): array
    {
        $order = Order::where('number', $arguments['order_number'])->first();

        if ($order === null) {
            return ['found' => false];
        }

        return ['found' => true, 'status' => $order->status];
    }
}
```

## Using dependency injection

Tools are resolved through the Laravel container (`$this->app->make()`), not instantiated with `new`, so a normal constructor works - you can type-hint any service, model repository, or `Yu\AiChatBot\Chat\CurrentConversation` (which gives you the UUID of the conversation currently being handled). See `Yu\AiChatBot\Llm\Tools\SwitchToHumanChatTool` for a real example that injects `CurrentConversation` and a package service (`TelegramClient`).

## Registering a tool

1. If you haven't already, publish the config: `php artisan vendor:publish --tag=ai-chat-config`.
2. Add your class to the `tools` array in `config/ai-chat.php`:

```php
'tools' => [
    \Yu\AiChatBot\Llm\Tools\CurrentDateTimeTool::class,
    \Yu\AiChatBot\Llm\Tools\SwitchToHumanChatTool::class,
    \App\AiChatTools\CheckOrderStatusTool::class,
],
```

That's it - the tool becomes available to the LLM on the next request.

## Things to keep in mind

- **`max_tool_rounds`** (config, default `3`) caps how many back-and-forth tool-calling rounds happen per customer message. If your tool is meant to be chained with others, make sure the flow fits in that budget.
- **Don't let `execute()` throw for expected failures.** An uncaught exception aborts the whole request and the customer sees a generic error. If a failure is something the model should explain to the customer (like "no operators available" in `SwitchToHumanChatTool`), catch it inside `execute()` and return a result describing the failure instead - then say how to interpret that result in `description()`.
- **Keep `execute()`'s return value small and structured.** It gets JSON-encoded and sent to the LLM as a message; returning entire Eloquent models or large collections wastes tokens and can leak fields you didn't mean to expose.

## Using an AI coding agent to build a tool

If you're using an AI coding agent (Claude Code, Cursor, Copilot, etc.) in the host app to build a tool, you can hand it a prompt like this - fill in the "what the tool should do" section for your specific case:

```
This Laravel app has the `yu-laravel/laravel-ai-chat-bot` package installed. Read
vendor/yu-laravel/laravel-ai-chat-bot/docs/creating-tools.md - it explains the
Yu\AiChatBot\Contracts\ToolInterface contract, how tools are resolved via the
Laravel container (so constructor DI works), and how to register a tool.

If config/ai-chat.php doesn't exist yet in this app, run:
   php artisan vendor:publish --tag=ai-chat-config

--------------------------------------------------------------------
What the tool should do:

<DESCRIBE HERE: what the customer is trying to accomplish, what data/model(s)
it needs to read (or write), what arguments the LLM should pass, and what a
successful vs. "nothing found" vs. "failed" result looks like.>
--------------------------------------------------------------------

Task:
1. Design the tool's name(), description(), and parameters() JSON Schema based
   on the behavior above. Pick a clear snake_case name() and a description()
   that also tells the LLM how to interpret each possible result (success /
   empty / failure) - the description is effectively an instruction to the
   model, not just a comment for humans.
2. Create the class under App\AiChatTools\ implementing ToolInterface.
   Use constructor DI for any Eloquent models/services it needs - don't use
   `new` or facades where a proper dependency would do.
3. In execute(): don't let expected failures throw - catch them and return a
   result describing the failure (see SwitchToHumanChatTool in the package
   for the pattern), so the model can explain it to the customer. Keep the
   returned array small - only what the model needs to answer, not full
   Eloquent models.
4. Register the new class in the `tools` array of config/ai-chat.php.
5. Tell me the tool's name(), description(), and parameters() schema you
   ended up with, so I can review the design before we test it.

Don't touch anything else in the ai-chat-bot package itself.
```

Step 5 is deliberate - review the model's name/description/parameters before testing, since a vague `description()` is the most common reason a tool gets called at the wrong time or not at all.
