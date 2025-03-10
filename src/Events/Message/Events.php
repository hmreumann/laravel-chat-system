<?php

namespace Myckhel\ChatSystem\Events\Message;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Myckhel\ChatSystem\Models\Message;
use Myckhel\ChatSystem\Traits\Config;

class Events implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $broadcastQueue = 'chat-event';
    public $event, $conversation_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($event){
      $this->event = $event;
      if (
        $event->type == 'delete'
        && $event->made_type == Config::config('models.message')
      ) {
        $this->conversation_id = $event->made?->conversation?->id;
        $event->unsetRelation('made');
      }
      $this->broadcastQueue = Config::config("queues.events.message.events");
    }

    public function broadcastAs()
    {
      return "message";
    }

    public function broadcastWhen()
    {
      return !($this->event->type == 'delete'
        && $this->event->made_type == Config::config('models.message')
        && !$this->conversation_id);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
      $event = $this->event;
      if ($event->type == 'delete' && !$event->all && $event->made_type == 'App\\Models\\Message') {
        return new PrivateChannel("message-event.user.{$this->event->maker_id}");
      } else {
        $participant_ids = $event->made->participants()->pluck('user_id')->toArray();
        return array_map(fn ($id) => new PrivateChannel("message-event.user.{$id}"), $participant_ids);
      }
    }
}
