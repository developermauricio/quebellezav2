<?php

// Exit if accessed directly
defined('ABSPATH') || exit;

// Load dependencies
require_once 'rightpress-condition-product.class.php';

/**
 * Condition: Product - Tags
 *
 * @class RightPress_Condition_Product_Tags
 * @package RightPress
 * @author RightPress
 */
abstract class RightPress_Condition_Product_Tags extends RightPress_Condition_Product
{

    protected $key      = 'tags';
    protected $method   = 'list_advanced';
    protected $fields   = array(
        'after' => array('product_tags'),
    );
    protected $position = 50;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

        parent::__construct();

        $this->hook();
    }

    /**
     * Get label
     *
     * @access public
     * @return string
     */
    public function get_label()
    {

        return esc_html__('Product tags', 'rightpress');
    }

    /**
     * Get value to compare against condition
     *
     * @access public
     * @param array $params
     * @return mixed
     */
    public function get_value($params)
    {

        // Check if item id is set
        if (empty($params['item_id'])) {
            throw new RightPress_Condition_Exception('rightpress_condition_value_error', 'RightPress Condition: Product is not defined.');
        }

        // Get product tag ids
        return RightPress_Help::get_wc_product_tag_ids_from_product_ids(array($params['item_id']));
    }





}
