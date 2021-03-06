<?php

    // dependency check
    if (class_exists('RequestCache') === false) {
        throw new Exception('RequestCache required.');
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
         * _bypass
         * 
         * @var    boolean
         * @access protected
         */
        protected static $_bypass = false;

        /**
         * _cacheType
         * 
         * @var    string
         * @access protected
         */
        protected static $_cacheType;

        /**
         * _deleteFromPersistentCache
         * 
         * @access protected
         * @static
         * @param  string $key
         * @return void
         */
        protected static function _deleteFromPersistentCache($key)
        {
            if (self::$_cacheType === 'memcached') {
                MemcachedCache::delete($key);
            } elseif (self::$_cacheType === 'apc') {
                APCCache::delete($key);
            }
        }

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
         * @param  integer $ttl
         * @return void
         */
        protected static function _writeToPersistentCache($key, $value, $ttl)
        {
            if (self::$_cacheType === 'memcached') {
                MemcachedCache::write($key, $value, $ttl);
            } elseif (self::$_cacheType === 'apc') {
                APCCache::write($key, $value, $ttl);
            }
        }

        /**
         * delete
         * 
         * @access public
         * @static
         * @param  string $key
         * @return void
         */
        public static function delete($key)
        {
            // ensure cache engine set
            if (is_null(self::$_cacheType)) {
                throw new Exception('_cacheType not set');
            }

            // write to delete from persistent cache
            RequestCache::simpleDelete($key);
            self::_deleteFromPersistentCache($key);
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
            // ensure cache engine set
            if (is_null(self::$_cacheType)) {
                throw new Exception('_cacheType not set');
            }

            // Bypassing checking
            if (self::$_bypass === true) {
                return null;
            }

            // check request and then persistent cache
            $requestCacheValue = RequestCache::read($key);
            if ($requestCacheValue === null) {
                $persistentCacheValue = self::_getPersistentCacheValue($key);
                if ($persistentCacheValue === null) {
                    return null;
                }
                RequestCache::write($key, $persistentCacheValue);
                return $persistentCacheValue;
            }
            return $requestCacheValue;
        }

        /**
         * setupBypassing
         * 
         * @access public
         * @static
         * @param  string $key The key, which if found in _GET, will turn
         *         caching off
         * @return void
         */
        public static function setupBypassing($key)
        {
            if (isset($_GET[$key])) {
                self::$_bypass = true;
            }
        }

        /**
         * write
         * 
         * @access public
         * @static
         * @param  string $key
         * @param  mixed $value
         * @param  integer $ttl (default: 0)
         * @return void
         */
        public static function write($key, $value, $ttl = 0)
        {
            // ensure cache engine set
            if (is_null(self::$_cacheType)) {
                throw new Exception('_cacheType not set');
            }

            // write to request and persistent cache
            RequestCache::write($key, $value);
            self::_writeToPersistentCache($key, $value, $ttl);
        }
    }
