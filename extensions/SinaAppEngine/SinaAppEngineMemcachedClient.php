<?php
/**
 * This is the PHP client for memcached - a distributed memory cache daemon.
 * More information is available at http://www.danga.com/memcached/
 *
 * Usage example:
 *
 * require_once 'memcached.php';
 *
 * $mc = new MWMemcached(array(
 *              'servers' => array('127.0.0.1:10000',
 *                                 array('192.0.0.1:10010', 2),
 *                                 '127.0.0.1:10020'),
 *              'debug'   => false,
 *              'compress_threshold' => 10240,
 *              'persistent' => true));
 *
 * $mc->add( 'key', array( 'some', 'array' ) );
 * $mc->replace( 'key', 'some random string' );
 * $val = $mc->get( 'key' );
 *
 * @author  Ryan T. Dean <rtdean@cytherianage.net>
 * @version 0.1.2
 */
 /**
 * _fwrite
 *   run_command
 *   _incrdecr
 *     decr
 *     incr
 *   _set
 *     add
 *     replace
 *     set
 *     cas
 * delete
 * get
 * get_multi
 * set_servers
 * __construct
 *
 * is  http://cn2.php.net/manual/zh/class.memcache.php
 * not http://cn2.php.net/manual/zh/class.memcached.php
 */
class MemCachedClientforWiki extends MWMemcached {
    var $_memcache = NULL;
	/**
	 * Command statistics
	 *
	 * @var     array
	 * @access  public
	 */
    var $stats;
    var $_debug;#boolean
    var $_have_zlib;#boolean
    var $_compress_enable;#boolean
	/**
	 * At how many bytes should we compress?
	 *
	 * @var     integer
	 * @access  private
	 */
    var $_compress_threshold;
/*
MemcachedBagOStuff::applyDefaultParams

array(7) {
  ["factory"]=>
  string(25) "ObjectCache::newMemcached"
  ["servers"]=>
  array(1) {
    [0]=>
    string(0) ""
  }
  ["debug"]=>
  bool(true)
  ["persistent"]=>
  bool(false)
  ["compress_threshold"]=>
  int(1500)
  ["timeout"]=>
  int(500000)
  ["connect_timeout"]=>
  float(0.5)
}
*/
	public function __construct( $args ) {
		$this->_memcache = memcache_init();
		$this->_debug = isset( $args['debug'] ) ? $args['debug'] : false;
		$this->stats = array();
		$this->_compress_threshold = isset( $args['compress_threshold'] ) ? $args['compress_threshold'] : 0;
		$this->_compress_enable = true;
		$this->_have_zlib = function_exists( 'gzcompress' );
	}
	
    /**
     * Performs the requested storage operation to the memcache server
     *
     * @param string $cmd command to perform
     * @param string $key key to act on
     * @param $val Mixed: what we need to store
     * @param $exp Integer: (optional) Expiration time. This can be a number of seconds
     * to cache for (up to 30 days inclusive).  Any timespans of 30 days + 1 second or
     * longer must be the timestamp of the time at which the mapping should expire. It
     * is safe to use timestamps in all cases, regardless of exipration
     * eg: strtotime("+3 hour")
     * @param $casToken[optional] Float
     *
     * @return Boolean
     * @access private
     */
    /**
     *   _set
     *     add      return $this->_set( 'add', $key, $val, $exp );
     *                     $this->_memcache->add( $key, $val, $flag, $expire );
     *
     *     replace	return $this->_set( 'replace', $key, $val, $exp );
     *                     $this->_memcache->replace( $key, $val, $flag, $expire );
     *
     *     set		return $this->_set( 'set', $key, $val, $exp );
     *                     $this->_memcache->set( $key, $val, $flag, $expire );
     *
     *     cas		return $this->_set( 'cas', $key, $value, $exp, $casToken );
     */
	public function cas( $casToken, $key, $value, $exp = 0 ) {
		return $this->_set( 'set', $key, $value, $exp );
	}
    function _set( $cmd, $key, $val, $exp, $casToken = null ) {
        if ( isset( $this->stats[$cmd] ) ) {
            $this->stats[$cmd]++;
        } else {
            $this->stats[$cmd] = 1;
        }

        $flags = 0;

        if ( !is_scalar( $val ) ) {
            $val = serialize( $val );
            $flags |= self::SERIALIZED;
            if ( $this->_debug ) {
                $this->_debugprint( sprintf( "client: serializing data as it is not scalar\n" ) );
            }
        }#works

        $len = strlen( $val );

        if ( $this->_have_zlib && $this->_compress_enable &&
            $this->_compress_threshold && $len >= $this->_compress_threshold )
        {
            $c_val = gzcompress( $val, 9 );
            $c_len = strlen( $c_val );

            if ( $c_len < $len * ( 1 - self::COMPRESSION_SAVINGS ) ) {
                if ( $this->_debug ) {
                    $this->_debugprint( sprintf( "client: compressing data; was %d bytes is now %d bytes\n", $len, $c_len ) );
                }
                $val = $c_val;
                $len = $c_len;
                $flags |= self::COMPRESSED;
            }
        }#works

		$result = $this->_memcache->$cmd( $key, $val, $flags, $expire );

        if ( $this->_debug ) {
            $this->_debugprint( sprintf( "%s %s (%s)\n", $cmd, $key, $result ) );
        }
        return $result;
    }

	/**
	 * Perform increment/decriment on $key
	 *
	 * @param string $cmd command to perform
	 * @param string|array $key key to perform it on
	 * @param $amt Integer amount to adjust
	 *
	 * @return Integer: new value of $key
	 * @access private
	 */
    /**
     *   _incrdecr
     *     decr		return $this->_incrdecr( 'decr', $key, $amt );
     *     incr		return $this->_incrdecr( 'incr', $key, $amt );
     */
	public function decr( $key, $amt = 1 ) {
		return $this->_incrdecr( 'decrement', $key, $amt );
	}
	public function incr( $key, $amt = 1 ) {
		return $this->_incrdecr( 'increment', $key, $amt );
	}
	function _incrdecr( $cmd, $key, $amt = 1 ) {
		$key = is_array( $key ) ? $key[1] : $key;
		if ( isset( $this->stats[$cmd] ) ) {
			$this->stats[$cmd]++;
		} else {
			$this->stats[$cmd] = 1;
		}

		return $this->_memcache->set( $key, $amt );
	}

	/**
	 * Deletes a key from the server, optionally after $time
	 *
	 * @param string $key key to delete
	 * @param $time Integer: (optional) how long to wait before deleting
	 *
	 * @return Boolean: TRUE on success, FALSE on failure
	 */
	public function delete( $key, $time = 0 ) {
		$key = is_array( $key ) ? $key[1] : $key;

		if ( isset( $this->stats['delete'] ) ) {
			$this->stats['delete']++;
		} else {
			$this->stats['delete'] = 1;
		}
		
		$result = $this->_memcache->delete($key);
		
		if ( $this->_debug ) {
			$this->_debugprint( sprintf( "MemCache: delete %s (%s)\n", $key, $result ) );
		}
		return $result;
	}

	/**
	 * Retrieves the value associated with the key from the memcache server
	 *
	 * @param array|string $key key to retrieve
	 * @param $casToken[optional] Float
	 *
	 * @return Mixed
	 */
	public function get( $key, &$casToken = null ) {
		wfProfileIn( __METHOD__ );

		if ( $this->_debug ) {
			$this->_debugprint( "get($key)\n" );
		}

		$key = is_array( $key ) ? $key[1] : $key;
		if ( isset( $this->stats['get'] ) ) {
			$this->stats['get']++;
		} else {
			$this->stats['get'] = 1;
		}

		$val = array();
		$val = $this->_load_items( array( $key ) );

		if ( $this->_debug ) {
			foreach ( $val as $k => $v ) {
				$this->_debugprint( sprintf( "MemCache: got %s\n", $k ) );
			}
		}

		wfProfileOut( __METHOD__ );
		return $val[ $key ];
	}

	/**
	 * Get multiple keys from the server(s)
	 *
	 * @param array $keys keys to retrieve
	 *
	 * @return Array
	 */
	public function get_multi( $keys ) {
		if ( isset( $this->stats['get_multi'] ) ) {
			$this->stats['get_multi']++;
		} else {
			$this->stats['get_multi'] = 1;
		}
		$gather_keys = array();
		foreach ( $keys as $key ) {
			$key = is_array( $key ) ? $key[1] : $key;
			$gather_keys[] = $key;
		}

		$val = array();
		$val = $this->_load_items( $gather_keys );

		if ( $this->_debug ) {
			foreach ( $val as $k => $v ) {
				$this->_debugprint( sprintf( "MemCache: got %s\n", $k ) );
			}
		}
		return $val;
	}

	/**
	 * Load items into $ret from $sock
	 *
	 * @param $sock Resource: socket to read from
	 * @param array $ret returned values
	 * @param $casToken[optional] Float
	 * @return boolean True for success, false for failure
	 *
	 * @access private
	 */
	function _load_items( $keys ) {
		$flag = 0;
		if ( $this->_have_zlib && $this->_compress_enable && self::COMPRESSED ) {
			$flag = self::COMPRESSED;
		}
		$results = $this->_memcache->get( $keys, $flag );
		$ret = array();
		/**
		 * All data has been read, time to process the data and build
		 * meaningful return values.
		 */
		foreach ( $results as $key => $value ) {
			if ( $this->_have_zlib && $this->_compress_enable && self::COMPRESSED ) {
				if ( is_string( $value ) && substr( bin2hex($value), 0, 4 ) === "78da" ) {
					$ret[$key] = gzuncompress( $value );
				} else {
					$ret[$key] = $value;
				}
			}

			/*
			 * This unserialize is the exact reason that we only want to
			 * process data after having read until "END" (instead of doing
			 * this right away): "unserialize" can trigger outside code:
			 * in the event that $ret[$rkey] is a serialized object,
			 * unserializing it will trigger __wakeup() if present. If that
			 * function attempted to read from memcached (while we did not
			 * yet read "END"), these 2 calls would collide.
			 */
			if ( is_scalar( $ret[$key] ) && self::SERIALIZED ) {
				$ret[$key] = unserialize( $ret[$key] );
			}
		}
		return $ret;
	}

    public function set_servers( $list ) {}
}