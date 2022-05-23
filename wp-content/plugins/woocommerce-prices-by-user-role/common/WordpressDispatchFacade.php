<?php
class WordpressDispatchFacade
{
    public function dispatchAction($actionName)
    {
        if (!is_array($actionName)) {
            $params = array($actionName);
        } else {
            $params = $actionName;
        }
        
        $args = func_get_args();
            
        array_shift($args);
        
        $params = array_merge($params, $args);

        call_user_func_array('do_action', $params);
    } // end dispatchAction
    
    
    public function dispatchFilter($filterName, $value)
    {
        $params = array(
            $filterName,
            $value
        );
        
        $args = func_get_args();
        
        $args = array_slice($args, 2);

        $params = array_merge($params, $args);

        return call_user_func_array('apply_filters', $params);
    } // end dispatchFilter
}