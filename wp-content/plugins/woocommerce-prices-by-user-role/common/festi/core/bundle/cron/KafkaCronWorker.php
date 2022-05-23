<?php

abstract class KafkaCronWorker extends CronWorker
{
    const OPTION_SEPARATOR = ',';
    const TIMEOUT = 300000;
    const PARTITION = 0;
    const AUTOCOMMIT_TIMEOUT = 100;
    
    const OPTION_BROKERS   = "brokers";
    const OPTION_TOPIC     = "topic";
    const OPTION_PARTITION = "partition";

    protected $consumer;
    protected $listener;
    
    /**
     * @override
     */
    protected function getOptions()
    {
        $params = array(
            'brokers:',
            'topic:',
            'id:',
            'partition:',
        );
        
        $options = getopt("", $params);
        
        if (!array_key_exists(static::OPTION_BROKERS, $options) || !$options[static::OPTION_BROKERS]) {
            throw new CronException("Undefined brokers");
        }
        
        if (!array_key_exists(static::OPTION_TOPIC, $options) || !$options[static::OPTION_TOPIC]) {
            throw new CronException("Undefined topics");
        }
        
        if (!array_key_exists('id', $options) || !$options['id']) {
            throw new CronException("Undefined id");
        }
        
        if (!array_key_exists(static::OPTION_PARTITION, $options) ||
            !$options[static::OPTION_PARTITION]) {
            $options[static::OPTION_PARTITION] = static::PARTITION;
        }
        
        $options[static::OPTION_BROKERS] = explode(
            static::OPTION_SEPARATOR, 
            $options[static::OPTION_BROKERS]
        );
        
        return $options;
    } // end getOptions
    
    /**
     * @override
     */
    protected function onInit()
    {
        if (!extension_loaded('rdkafka')) {
            throw new CronException("Not found PHP module: rdkafka");
        }

        $this->onInitConsumer();
        $this->onInitListener();
    } // end onInit
    
    protected function onInitConsumer()
    {
        $brokers = $this->getOption(static::OPTION_BROKERS);
        $conf    = $this->getConsumerConfig();
        
        $this->consumer = new RdKafka\Consumer($conf);
        if ($brokers && is_array($brokers)) {
            $this->consumer->addBrokers(implode(',', $brokers));
        }
    } // end onInitConsumer
    
    protected function onInitListener()
    {
        $topicConf = $this->getTopicConfig();
    
        $partition = $this->getOption(static::OPTION_PARTITION);
        $topicName = $this->getOption(static::OPTION_TOPIC);
        $this->listener = $this->consumer->newTopic($topicName, $topicConf);
        
        $this->listener->consumeStart(
            $partition, 
            RD_KAFKA_OFFSET_STORED
        );
    } // end onInitListener
    
    /**
     * @override
     */
    protected function getSpool()
    {
        $returnStatuses = array(
            RD_KAFKA_RESP_ERR__PARTITION_EOF, 
            RD_KAFKA_RESP_ERR__TIMED_OUT
        );
        
        $rows = array();
        while (true) {
            $message = $this->getRemoteMessage();
            
            if ($message === NULL || in_array($message->err, $returnStatuses)) {
                break;
            } else if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                $rows[] = array(
                    static::OPTION_TOPIC     => $message->topic_name,
                    'error'                  => $message->err,
                    static::OPTION_PARTITION => $message->partition,
                    'data'                   => $message->payload,
                    'key'                    => $message->key,
                    'offset'                 => $message->offset
                );
            } else {
                throw new CronException($message->errstr(), $message->err);
            }
        }
        
        return $rows;
    } // end getSpool

    protected function getRemoteMessage()
    {
        $partition = $this->getOption(static::OPTION_PARTITION);
        
        return $this->listener->consume($partition, static::TIMEOUT);
    } // end getRemoteMessage
    
    protected function getTopicConfig()
    {
        $topicConf = new RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', static::AUTOCOMMIT_TIMEOUT);
    
        // Set the offset store method to 'file'
        $topicConf->set('offset.store.method', 'file');
        $topicConf->set('offset.store.path', sys_get_temp_dir());
    
        // Alternatively, set the offset store method to 'broker'
        // $topicConf->set('offset.store.method', 'broker');
    
        // Set where to start consuming messages when there is 
        // no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $topicConf->set('auto.offset.reset', 'smallest');
        
        return $topicConf;
    } // end getTopicConfig
    
    protected function getConsumerConfig()
    {
        $conf = new RdKafka\Conf();
    
        $conf->set('group.id', $this->getOption('id'));
        
        return $conf;
    } // end getConsumerConfig
}