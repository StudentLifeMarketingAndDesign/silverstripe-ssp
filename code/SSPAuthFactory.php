<?php

/**
 * Factory class for loading the active SSPAuthenticator class
 * 
 * @package silverstripe-ssp
 * @author Anton Smith <anton.smith@op.ac.nz>
 */
class SSPAuthFactory {
    
    /**
     * Gets the current SSPAuthenticator class used for SimpleSAMLphp authentication
     * @return SSPAuthenticator Active session for authentication
     */
    public static function get_authenticator() {
        
        if(!is_null(Session::get('ssp_current_auth'))) {
            $ssp = unserialize(Session::get('ssp_current_auth'));   

            if($ssp->isAuthenticated()) {
                return $ssp;   
            }
            
            else {
                Session::clear('SimpleSAMLphp_SESSION');
                Session::clear('ssp_current_auth');
            }
        }
        
        return self::init_authenticator();   
    }
    
    /**
     * Initialises the selected SSPAuthenticator class for SimpleSAMLphp authentication
     * @return SSPAuthenticator Active session for authentication
     */
    private static function init_authenticator() {
        $authenticators = SSPSecurity::config()->authenticators;
        
        if(!$authenticators || !is_array($authenticators)) {
            user_error("Expected array of authentication sources in SSPSecurity::authenticators", E_USER_ERROR);
        }
        
        $auth_source = '';

        //If set, override authentication sources in config
        if(isset($_GET['as'])) {
            $auth_source = $_GET['as'];
        }
        
        //Else look for the default authentication source
        else {
            $default_auth = SSPSecurity::config()->default_authenticator;
            
            $env = Director::get_environment_type();
            
            if(is_string($default_auth)) {
                $auth_source = $default_auth;
            }
            
            //Use the current environment defined in the config
            else if(is_array($default_auth) && array_key_exists($env, $default_auth)) {
                $auth_source = $default_auth[$env];
            }
        }
        
        //If no auth_source is found, default to the first authentication source
        if(empty($auth_source)) {
            $auth_source = key($authenticators);
        }
        
        if(!array_key_exists($auth_source, $authenticators)) {
            user_error("'$auth_source' does not exist in SSPSecurity::authenticators", E_USER_ERROR);
        }

        $class = $authenticators[$auth_source];
        
        if(!class_exists($class)) {
            user_error("$class does not exist", E_USER_ERROR);
        }

        if(!is_subclass_of($class, 'SSPAuthenticator')) {
            user_error("$class does not extend from SSPAuthenticator", E_USER_ERROR);
        }

        $authenticator = new $class($auth_source);

        return $authenticator;   
    }
}
