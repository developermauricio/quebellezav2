<?php
/**
*
* author: Denis Panaskin <goliathdp@gmail.com>
* version: Beta 1.0 28.04.2007
*/
interface IEventDispatcher
{
    public function addEventListener($type, $listener);
    public function dispatchEvent(FestiEvent &$event): ?array;
    public function removeEventListener($type, $listener);
}
