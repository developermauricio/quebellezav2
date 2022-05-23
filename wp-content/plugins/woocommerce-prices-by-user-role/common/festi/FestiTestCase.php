<?php

class FestiTestCase extends WP_UnitTestCase
{
    protected $idUserAdmin;

    protected function setMainPage(WP_Post $page)
    {
        $facade = EngineFacade::getInstance();

        $facade->updateOption('page_on_front', $page->ID);
        $facade->updateOption('show_on_front', 'page');
    } // end setMainPage
    
    protected function doAction($name)
    {
        ob_start();
        
        EngineFacade::getInstance()->dispatchAction($name);
        
        return ob_get_clean();
    } // end doAction
    
    protected function createPage($options = array())
    {
        $options['post_type'] = 'page';
        
        return $this->createPost($options);
    } // end createPage
    
    protected function createPost(&$options)
    {
        if (empty($options['post_title'])) {
            $options['post_title'] = 'content_'.rand(1000, 100000);
        }
        
        if (empty($options['post_type'])) {
            $options['post_type'] = 'post';
        }
        
        return self::factory()->post->create_and_get($options);
    } // end createPost
    
    protected function createWooProduct()
    {
        $options = array(
            'post_type' => 'product'
        );
        
        $post = $this->createPost($options);
        
        $idPost = $post->ID;

        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $regularPriceKey= WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $facade = EngineFacade::getInstance();

        $facade->setObjectTerms($idPost, 'simple', 'product_type');

        $facade->updatePostMeta($idPost, '_visibility', 'visible');
        $facade->updatePostMeta($idPost, '_stock_status', 'instock');
        $facade->updatePostMeta($idPost, 'total_sales', '0');
        $facade->updatePostMeta($idPost, '_downloadable', 'yes');
        $facade->updatePostMeta($idPost, '_virtual', 'yes');
        $facade->updatePostMeta($idPost, $regularPriceKey, '1');
        $facade->updatePostMeta($idPost, $salePriceKey, '1');
        $facade->updatePostMeta($idPost, '_purchase_note', '');
        $facade->updatePostMeta($idPost, '_featured', 'no');
        $facade->updatePostMeta($idPost, '_weight', '');
        $facade->updatePostMeta($idPost, '_length', '');
        $facade->updatePostMeta($idPost, '_width', '');
        $facade->updatePostMeta($idPost, '_height', '');
        $facade->updatePostMeta($idPost, '_sku', '');
        $facade->updatePostMeta($idPost, '_product_attributes', array());
        $facade->updatePostMeta($idPost, '_sale_price_dates_from', '');
        $facade->updatePostMeta($idPost, '_sale_price_dates_to', '');
        $facade->updatePostMeta($idPost, $priceKey, '1');
        $facade->updatePostMeta($idPost, '_sold_individually', '');
        $facade->updatePostMeta($idPost, '_manage_stock', 'no');
        $facade->updatePostMeta($idPost, '_backorders', 'no');
        $facade->updatePostMeta($idPost, '_stock', '');
        $facade->updatePostMeta($idPost, '_download_limit', '');
        $facade->updatePostMeta($idPost, '_download_expiry', '');
        $facade->updatePostMeta($idPost, '_download_type', '');
        $facade->updatePostMeta($idPost, '_product_image_gallery', '');
    
        return $idPost;
    } // end createWooProduct

    protected function getCountWpFilterHookCallBacks($hookName, $methodName)
    {
        if (!array_key_exists('wp_filter', $GLOBALS)) {
            return false;
        }

        $wpFilter = $GLOBALS['wp_filter'];

        if (!array_key_exists($hookName, $wpFilter)) {
            return false;
        }

        $wpFilterCallbacks = $wpFilter[$hookName];

        if (is_object($wpFilterCallbacks)) {
            $wpFilterCallbacks = $wpFilterCallbacks->callbacks;
        }

        $wpFilterCallbacks = array_shift($wpFilterCallbacks);

        $count = false;

        foreach ($wpFilterCallbacks as $key => $value) {
            if (strpos($key, $methodName)) {
                $count += 1;
            };
        }

        return $count;
    } // end getCountWpFilterHookCallBacks

    protected function setValueReflectionProperty($object, $property, $value)
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    } // end setValueReflectionProperty

    protected function doCreateProduct()
    {
        $wpError = false;

        $post = array(
            'post_author' => $this->idUserAdmin,
            'post_content' => '',
            'post_status' => 'publish',
            'post_title' => 'Test product',
            'post_parent' => '',
            'post_type' => 'product',
        );

        $facade = EngineFacade::getInstance();


        $idPost = $facade->doInsertPost($post, $wpError);
        
        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;


        $facade->setObjectTerms($idPost, 'simple', 'product_type');

        $facade->updatePostMeta($idPost, '_visibility', 'visible');
        $facade->updatePostMeta($idPost, '_stock_status', 'instock');
        $facade->updatePostMeta($idPost, 'total_sales', '0');
        $facade->updatePostMeta($idPost, '_downloadable', 'yes');
        $facade->updatePostMeta($idPost, '_virtual', 'yes');
        $facade->updatePostMeta($idPost, $regularPriceKey, '1');
        $facade->updatePostMeta($idPost, $salePriceKey, '1');
        $facade->updatePostMeta($idPost, '_purchase_note', '');
        $facade->updatePostMeta($idPost, '_featured', 'no');
        $facade->updatePostMeta($idPost, '_weight', '');
        $facade->updatePostMeta($idPost, '_length', '');
        $facade->updatePostMeta($idPost, '_width', '');
        $facade->updatePostMeta($idPost, '_height', '');
        $facade->updatePostMeta($idPost, '_sku', '');
        $facade->updatePostMeta($idPost, '_product_attributes', array());
        $facade->updatePostMeta($idPost, '_sale_price_dates_from', '');
        $facade->updatePostMeta($idPost, '_sale_price_dates_to', '');
        $facade->updatePostMeta($idPost, $priceKey, '1');
        $facade->updatePostMeta($idPost, '_sold_individually', '');
        $facade->updatePostMeta($idPost, '_manage_stock', 'no');
        $facade->updatePostMeta($idPost, '_backorders', 'no');
        $facade->updatePostMeta($idPost, '_stock', '');
        $facade->updatePostMeta($idPost, '_download_limit', '');
        $facade->updatePostMeta($idPost, '_download_expiry', '');
        $facade->updatePostMeta($idPost, '_download_type', '');
        $facade->updatePostMeta($idPost, '_product_image_gallery', '');

        $this->products['simple']['id'] = $idPost;
    } // end doCreateProduct
}