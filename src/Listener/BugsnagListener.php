<?php

namespace Bugsnag\Listener;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

class BugsnagListener implements EventListenerInterface
{
    /**
     * List of implemented events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Log.Bugsnag.beforeNotify' => 'beforeNotify'
        ];
    }
    /**
     * Format stacktrace.
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function beforeNotify(Event $event)
    {
        $error = $event->getData('error'); /* @var $error \Bugsnag_Error */
        if(!empty($error->metaData["session"]["Auth"]["User"]))
            $user = $error->metaData["session"]["Auth"]["User"];
        else $user = null;
        $oldMessage = $error->message;
        $error->setMessage(strtok($error->message, "\n"));
        $error->setUser($user);
        $request = Router::getRequest();
        $meta = [
            'details' => [
                'Referer' => $request->referer(),
                'ServerParams' => $request->getServerParams(),
                'QueryParams' => $request->getQueryParams(),
                'Data' => $request->getData(),

            ],
            'message_old' => $oldMessage
        ];
        $error->setMetaData($meta);
        $offset = null;
        foreach ( $error->stacktrace->frames as $index => $frame ) {
            if(stripos($error->message, $frame['file']) !== false) {
                $offset = $index;
                break;
            }
        }
        if($offset == null)
            $offset = 3;

        $frames                    = array_slice( $error->stacktrace->frames, $offset);
        $error->stacktrace->frames = $frames;

    }
}
