<?php

if (!class_exists('EventDispatcher')):

    require_once dirname(__FILE__)."/IEventDispatcher.php";
    require_once dirname(__FILE__)."/FestiEvent.php";
    
    /**
     * @author: Denis Panaskin <goliathdp@gmail.com>
     * @version: RC 1.0
     * @since 2007
     */
    class EventDispatcher extends ArrayObject implements IEventDispatcher
    {
        private $__listeners = array();
    
        public function __construct()
        {
            parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
        }
    
        /**
         * Execute all handlers attached to the matched elements for the given 
         * event type.
         * 
         * $this->addEventListener("Init", array(&$this, "f"));
         * $this->addEventListener("Init", ty);
         *
         * @param mixed $type
         * @param mixed $listener
         * @return boolean
         */
        public function addEventListener($type, $listener)
        {
            if (!is_callable($listener)) {
                return false;
            }
             
            $key = $this->_getKey($listener);
            
            $this->__listeners[$type][$key] = $listener;
            
            return true;
        } // end addEventListener
    
        /**
         * @param FestiEvent $event
         * @return bool
         */
        private function _hasEventListeners(FestiEvent &$event): bool
        {
            return array_key_exists($event->getType(), $this->__listeners);
        } // end _hasEventListeners
        
        /**
         * @param FestiEvent $event
         * @return array|null
         */
        public function dispatchEvent(FestiEvent &$event): ?array
        {
            if (!$this->_hasEventListeners($event)) {
                return null;
            }
            
            $externalParams = func_get_args();
            array_shift($externalParams);
            
            $params = array(
                &$event
            );
            
            if ($externalParams) {
                $params = array_merge($params, $externalParams);
            }
    
            $results = array();
            foreach ($this->__listeners[$event->type] as $listener) {
                if (!is_callable($listener)) {
                    continue;
                }

                $results[] = call_user_func_array($listener, $params);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
    
            return $results;
        } // end dispatchEvent
    
        /**
         * @param $type
         * @param $listener
         * @throws SystemException
         */
        public function removeEventListener($type, $listener)
        {
            $key = $this->_getKey($listener);

            if (!array_key_exists($type, $this->__listeners)) {
                $msg = __('Not Found Listener, Type %s', $type);
                throw new SystemException($msg);
            }
            
            if (!array_key_exists($key, $this->__listeners[$type])) {
                $msg = __('Not Found Listener, Type %s, Key %s', $type, $key);
                throw new SystemException($msg);
            }
            
            unset($this->__listeners[$type][$key]);
        } // end removeEventListener

        /**
         * Returns TRUE if the listener is added already.
         *
         * @param $type
         * @param $listener
         * @return bool
         */
        public function hasEventListener($type, $listener)
        {
            $key = $this->_getKey($listener);

            return array_key_exists($type, $this->__listeners) &&
                   array_key_exists($key, $this->__listeners[$type]);
        } // end hasEventListener
    
        /**
         * @param $listener
         * @return string
         */
        private function _getKey($listener): string
        {
            if ($listener instanceof Closure) {
                return spl_object_hash($listener);
            }
            if (is_array($listener)) {
                return spl_object_hash($listener[0]).":".$listener[1];
            }
    
            return 'f:'.$listener;
        } // end _getKey
    
        /**
         * @return array
         */
        public function getEventListeners(): array
        {
            return $this->__listeners;
        } // end getEventListeners
    }
endif;
