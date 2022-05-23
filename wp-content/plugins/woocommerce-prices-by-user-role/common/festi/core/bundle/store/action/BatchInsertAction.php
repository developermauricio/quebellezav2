<?php

require_once 'bundle/store/action/InsertAction.php';

class BatchInsertAction extends InsertAction
{
    /**
     * @param Response $response
     * @return array|null
     * @throws StoreException
     */
    public function onUpdate(Response &$response): ?array
    {
        $request = $this->getRequest();
        $vars    = array();

        if (!$request) {
            throw new StoreException(__('Can`t Find Request'));
        }
        
        $proxy = &$this->store->getProxy();
        
        $inTransaction = $proxy->isBegin();
        
        try {
            if (!$inTransaction) {
                $proxy->begin();
            }

            foreach ($request as $values) {
                $this->primaryKeyValue = null;
                $res = $this->apply($values, $response);
                if (!$res) {
                    $message = $this->getLastError();
                    throw new SystemException($message);
                }

                $vars[] = $res;
            }

        } catch (Exception $exp) {
            $this->doHandleException($exp);
            return null;
        }
        
        if (!$inTransaction) {
            $proxy->commit();
        }
        
        return $vars;
    } // end onUpdate

    protected function fireCompleteEvent(): void
    {
        $target = array(
            'instance' => &$this,
            'result'   => &$this->updateInfo,
            'action'   => $this->store->getAction()
        );

        $type = false;
        if ($this->updateInfo['action'] == Store::ACTION_BATCH_INSERT) {
            $type = Store::EVENT_INSERT;
        }

        if (!$type) {
            throw new SystemException(__("Undefined action"));
        }

        $event = new FestiEvent($type, $target);
        $this->store->dispatchEvent($event);

        $event = new FestiEvent(Store::EVENT_UPDATE_VALUES, $target);
        $this->store->dispatchEvent($event);
    } // end fireCompleteEvent

}
