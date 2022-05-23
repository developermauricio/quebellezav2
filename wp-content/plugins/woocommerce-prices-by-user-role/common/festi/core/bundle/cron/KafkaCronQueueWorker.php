<?php

abstract class KafkaCronQueueWorker extends KafkaCronWorker
{
    const OPTION_TOPIC = 'topic';
    /**
     * @override
     */
    protected function getOptions()
    {
        $options = parent::getOptions();
        
        $options[static::OPTION_TOPIC] = explode(
            static::OPTION_SEPARATOR, 
            $options[static::OPTION_TOPIC]
        );
        
        return $options;
    } // end getOptions
    
    /**
     * @override
     */
    protected function onInitListener()
    {
        $topicConf = $this->getTopicConfig();
        $topics    = $this->getOption(static::OPTION_TOPIC);
        $partition = $this->getOption('partition');
        
        $this->listener = $this->consumer->newQueue();
        if (!$topics) {
            return;
        }
       
        foreach ($topics as $topicName) {
            $topic = $this->consumer->newTopic($topicName, $topicConf);
            $topic->consumeQueueStart(
                $partition, 
                RD_KAFKA_OFFSET_STORED, 
                $this->listener
            );
        }
    } // end onInitListener
    
    /**
     * @override
     */
    protected function getRemoteMessage()
    {
        return $this->listener->consume(static::TIMEOUT);
    } // end getRemoteMessage
    
    protected function getTopicConfig()
    {
        $topicConf = new RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);
    
        $topicConf->set('offset.store.method', 'file');
        $topicConf->set('offset.store.path', sys_get_temp_dir());
    
        $topicConf->set('auto.offset.reset', 'smallest');
        
        return $topicConf;
    } // end getTopicConfig
}