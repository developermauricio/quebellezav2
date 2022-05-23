<?php

class WooUserRolePricesUtils
{
    public static function doCheckPhpVersion($minVersion)
    {
        if (version_compare(phpversion(), $minVersion, '<')) {
            $message = 'The minimum PHP version required for this plugin is '.
                       $minVersion.'. Please contact your hosting company to '.
                       'upgrade PHP version on your server.';
                    
            throw new Exception(
                $message,
                PRICE_BY_ROLE_EXCEPTION_INVALID_PHP_VERSION
            );
        }
    } // end doCheckPhpVersion
    
    public static function displayPluginError($message)
    {
        $facade = EngineFacade::getInstance();

        $facade->setTransient(PRICE_BY_ROLE_EXCEPTION_MESSAGE, $message);

        $facade->addActionListener(
            'admin_notices',
            array(
                new WooUserRolePricesUtils(),
                "fetchExceptionMessage"
            )
        );
    } // end displayPluginError
    
    public function fetchExceptionMessage()
    {
        $facade = EngineFacade::getInstance();

        $message = $facade->getTransient(PRICE_BY_ROLE_EXCEPTION_MESSAGE);

        echo '<div class="error"> <p>'.$message.'</p></div>';
    } // end fetchExceptionMessage

    public static function isSessionStarted()
    {
        $facade = EngineFacade::getInstance();

        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return session_id() !== '';
            }
        } else if ($facade->isTestEnvironmentDefined()) {
            return true;
        }

        return false;
    } // end isSessionStarted
}