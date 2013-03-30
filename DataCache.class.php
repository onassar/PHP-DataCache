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
     * @note     Should on be used with native data structures. Don't try
     *           caching stdClass', or something of that ilk.
     * @author   Oliver Nassar <onassar@gmail.com>
     * @abstract
     */
    abstract class DataCache
    {
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
                $memcachedCacheValue = MemcachedCache::read($key);
                if ($memcachedCacheValue === null) {
                    return null;
                }
                return $memcachedCacheValue;
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
            MemcachedCache::write($key, $value);
        }
    }
