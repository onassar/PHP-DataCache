<?php

    // dependency checks
    if (class_exists('RequestCache') === false) {
        throw new Exception('RequestCache required.');
    } elseif (class_exists('MemcachedCache') === false) {
        throw new Exception('MemcachedCache required.');
    }

    /**
     * DataCache
     * 
     * @note     Should only be used with native data structures. Don't try
     *           caching stdClass', or something of that ilk.
     * @author   Oliver Nassar <onassar@gmail.com>
     * @abstract
     */
    abstract class DataCache
    {
        /**
         * _cacheType
         * 
         * @var    string
         * @access protected
         */
        protected static $_cacheType;

        /**
         * _getPersistentCacheValue
         * 
         * @access protected
         * @static
         * @param  string $key
         * @return mixed
         */
        protected static function _getPersistentCacheValue($key)
        {
            if (self::$_cacheType === 'memcached') {
                return MemcachedCache::read($key);
            } elseif (self::$_cacheType === 'apc') {
                return APCCache::read($key);
            }
        }

        /**
         * _writeToPersistentCache
         * 
         * @access protected
         * @static
         * @param  string $key
         * @param  mixed $value
         * @return void
         */
        protected static function _writeToPersistentCache($key, $value)
        {
            if (self::$_cacheType === 'memcached') {
                MemcachedCache::write($key, $value);
            } elseif (self::$_cacheType === 'apc') {
                APCCache::write($key, $value);
            }
        }

        /**
         * init
         * 
         * @access public
         * @static
         * @param  string $cacheType
         * @return void
         */
        public static function init($cacheType)
        {
            if (!in_array($cacheType, array('apc', 'memcached'))) {
                throw new Exception('Invalid cache type specified.');
            }
            self::$_cacheType = $cacheType;
        }

        /**
         * read
         * 
         * @access public
         * @static
         * @param  string $key
         * @return mixed
         */
        public static function read($key)
        {
            $requestCacheValue = RequestCache::read($key);
            if ($requestCacheValue === null) {
                $persistentCacheValue = self::_getPersistentCacheValue($key);
                if ($persistentCacheValue === null) {
                    return null;
                }
                return $persistentCacheValue;
            }
            return $requestCacheValue;
        }

        /**
         * write
         * 
         * @access public
         * @static
         * @param  string $key
         * @param  mixed $value
         * @return void
         */
        public static function write($key, $value)
        {
            RequestCache::write($key, $value);
            self::_writeToPersistentCache($key, $value);
        }
    }
