<?php
/**
 * File system based backend.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup FileBackend
 * @author Aaron Schulz
 */

/**
 * @brief Class for a file system (FS) based file backend.
 *
 * All "containers" each map to a directory under the backend's base directory.
 * For backwards-compatibility, some container paths can be set to custom paths.
 * The wiki ID will not be used in any custom paths, so this should be avoided.
 *
 * Having directories with thousands of files will diminish performance.
 * Sharding can be accomplished by using FileRepo-style hash paths.
 *
 * Status messages should avoid mentioning the internal FS paths.
 * PHP warnings are assumed to be logged rather than output.
 *
 * @ingroup FileBackend
 * @since 1.19
 */
class SinaAppEngineFileBackend extends FileBackendStore {
    /**
     * @see $wgFileBackends
     */
    public function __construct( array $config ) {
        parent::__construct( $config );
    }

    /**
     * Given the short (unresolved) and full (resolved) name of
     * a container, return the file system path of the container.
     *
     * @param string $shortCont
     * @param string $fullCont
     * @return string|null
     */
    protected function containerFSRoot( $shortCont, $fullCont ) {
        if ( isset( $this->containerPaths[$shortCont] ) ) {
            return $this->containerPaths[$shortCont];
        } elseif ( isset( $this->basePath ) ) {
            return "{$this->basePath}/{$fullCont}";
        }
        return null; // no container base path defined
    }

    /**
     * Get the absolute file system path for a storage path
     *
     * @param string $storagePath Storage path
     * @return string|null
     */
    protected function resolveToFSPath( $storagePath ) {
        global $wgUploadDirectory;

        list( $fullCont, $relPath ) = $this->resolveStoragePathReal( $storagePath );
        if ( $relPath === null ) {
            return null; // invalid
        }
        list( , $shortCont, ) = FileBackend::splitStoragePath( $storagePath );
        $fsPath = $this->containerFSRoot( $shortCont, $fullCont ); // must be valid
        /*if ( $shortCont != '' ) {
            $fsPath .= "/{$shortCont}";
        }*/
        if ( $relPath != '' ) {
            $fsPath .= "/{$relPath}";
        }
        return $wgUploadDirectory . $fsPath;
        #return $fsPath;
    }

    /**
     * @see FileBackendStore::isPathUsableInternal()
     * @return bool
     */
    public function isPathUsableInternal( $storagePath ) {
        return true;
    }

    /**
     * @see FileBackendStore::doCreateInternal()
     * @return Status
     */
    protected function doCreateInternal( array $params ) {
		$status = Status::newGood();

		$dest = $this->resolveToFSPath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		$bytes = file_put_contents( $dest, $params['content'] );
		if ( $bytes === false ) {
			$status->fatal( 'backend-fail-create', $params['dst'] );
			return $status;
		}

		return $status;
    }

    /**
     * @see FileBackendStore::doStoreInternal()
     * @return Status
     */
    protected function doStoreInternal( array $params ) {
		$status = Status::newGood();

		$dest = $this->resolveToFSPath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		$ok = copy( $params['src'], $dest );
		// In some cases (at least over NFS), copy() returns true when it fails
		if ( !$ok || ( filesize( $params['src'] ) !== filesize( $dest ) ) ) {
			if ( $ok ) { // PHP bug
				unlink( $dest ); // remove broken file
				trigger_error( __METHOD__ . ": copy() failed but returned true." );
			}
			$status->fatal( 'backend-fail-store', $params['src'], $params['dst'] );
			return $status;
		}

		return $status;
	}

    /**
     * @see FileBackendStore::doCopyInternal()
     * @return Status
     */
    protected function doCopyInternal( array $params ) {
		$status = Status::newGood();

		$source = $this->resolveToFSPath( $params['src'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		$dest = $this->resolveToFSPath( $params['dst'] );
		if ( $dest === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['dst'] );
			return $status;
		}

		if ( !is_file( $source ) ) {
			if ( empty( $params['ignoreMissingSource'] ) ) {
				$status->fatal( 'backend-fail-copy', $params['src'] );
			}
			return $status; // do nothing; either OK or bad status
		}

		$ok = ( $source === $dest ) ? true : copy( $source, $dest );
		// In some cases (at least over NFS), copy() returns true when it fails
		if ( !$ok || ( filesize( $source ) !== filesize( $dest ) ) ) {
			if ( $ok ) { // PHP bug
				unlink( $dest ); // remove broken file
				trigger_error( __METHOD__ . ": copy() failed but returned true." );
			}
			$status->fatal( 'backend-fail-copy', $params['src'], $params['dst'] );
			return $status;
		}

		return $status;
    }

    /**
     * @see FileBackendStore::doDeleteInternal()
     * @return Status
     */
    protected function doDeleteInternal( array $params ) {
		$status = Status::newGood();

		$source = $this->resolveToFSPath( $params['src'] );
		if ( $source === null ) {
			$status->fatal( 'backend-fail-invalidpath', $params['src'] );
			return $status;
		}

		if ( !is_file( $source ) ) {
			if ( empty( $params['ignoreMissingSource'] ) ) {
				$status->fatal( 'backend-fail-delete', $params['src'] );
			}
			return $status; // do nothing; either OK or bad status
		}

		$ok = unlink( $source );
		if ( !$ok ) {
			$status->fatal( 'backend-fail-delete', $params['src'] );
			return $status;
		}

		return $status;
    }

    /**
     * @see FileBackendStore::doFileExists()
     * @return array|bool|null
     */
    protected function doGetFileStat( array $params ) {
		$source = $this->resolveToFSPath( $params['src'] );
		if ( $source === null ) {
			return false; // invalid storage path
		}

		$stat = is_file( $source ) ? stat( $source ) : false; // regular files only
		if ( $stat ) {
			return array(
				'mtime' => wfTimestamp( TS_MW, $stat['mtime'] ),
				'size' => $stat['size']
			);
		} elseif ( !$hadError ) {
			return false; // file does not exist
		} else {
			return null; // failure
		}
    }

    /**
     * @see FileBackendStore::doDirectoryExists()
     * @return bool|null
     */
    protected function doDirectoryExists( $fullCont, $dir, array $params ) {
        return true;//null
    }

    /**
     * @see FileBackendStore::getDirectoryListInternal()
     * @return SinaAppEngineFileBackendDirList
	 * @return Array|null
     * delete, move
     */
    public function getDirectoryListInternal( $fullCont, $dir, array $params ) {
    	global $wgUploadPath;
    	
		$params = FileBackend::splitStoragePath( $params['dir'] );
		$list = array();
		$s = new SaeStorage();
		$li = $s->getListByPath( $params[0], "{$wgUploadPath}/{$params[2]}/", 1000, 0 );
		foreach( $li["dirs"] as $dir ) {;
			#$list[] = str_replace( "mediawiki/images/{$params[2]}/", "", $dir["fullName"] );
			$list[] = $dir["name"];
		}
		return $list;
        #return new SinaAppEngineFileBackendDirList( $this, $fullCont, $dir, $params );
    }

    /**
     * @see FileBackendStore::getFileListInternal()
     * @return SinaAppEngineFileBackendFileList
	 * @return Array|FSFileBackendFileList|null
     */
    public function getFileListInternal( $fullCont, $dir, array $params ) {
    	global $wgUploadPath;
    	
		$params = FileBackend::splitStoragePath( $params['dir'] );#domain/container/path
		$dir = trim( "{$wgUploadPath}/{$params[2]}", '/');

		$list = array();
		$s = new SaeStorage();
		$files = $s->getList( SAESTOR_DOMAIN, $dir, 100, 0 );
		foreach( $files as $file ) {
			$list[] = str_replace( $dir, "", $file );
		}
		#$li = $s->getListByPath( $params[0], "mediawiki/images/{$params[2]}/", 100, 0 );
		#foreach( $li["files"] as $file ) {;
		#	$list[] = str_replace( "mediawiki/images/{$params[2]}/", "", $file["fullName"] );
		#}
		return $list;
        #return new SinaAppEngineFileBackendFileList( $this, $fullCont, $dir, $params );
    }

    /**
     * @see FileBackendStore::doGetLocalCopyMulti()
     * @return null|TempFSFile
     */
    protected function doGetLocalCopyMulti( array $params ) {
		$tmpFiles = array(); // (path => TempFSFile)

		foreach ( $params['srcs'] as $src ) {
			$source = $this->resolveToFSPath( $src );
			if ( $source === null ) {
				$tmpFiles[$src] = null; // invalid path
			} else {
				// Create a new temporary file with the same extension...
				$ext = FileBackend::extensionFromPath( $src );
				$tmpFile = TempFSFile::factory( 'localcopy_', $ext );
				if ( !$tmpFile ) {
					$tmpFiles[$src] = null;
				} else {
					$tmpPath = $tmpFile->getPath();
					// Copy the source file over the temp file
					$ok = copy( $source, $tmpPath );
					if ( !$ok ) {
						$tmpFiles[$src] = null;
					} else {
						$tmpFiles[$src] = $tmpFile;
					}
				}
			}
		}

		return $tmpFiles;
    }

    /**
     * @see FileBackendStore::directoriesAreVirtual()
     * @return bool
     */
    protected function directoriesAreVirtual() {
        return true;
    }
}

/**
 * @see FileBackendStoreOpHandle
 */
class SinaAppEngineFileOpHandle extends FileBackendStoreOpHandle {}

/**
 * SinaAppEngineFileBackend helper class to page through listings.
 * SinaAppEngine also has a listing limit of 10,000 objects for sanity.
 * Do not use this class from places outside SinaAppEngineFileBackend.
 *
 * @ingroup FileBackend
 * http://en.wikipedia.org/wiki/Iterator#PHP
 * http://www.php.net/manual/zh/class.iterator.php
 1. rewind()
 2. while valid() {
       2.1 current() in $value
       2.3 key() in $key
       2.4 next()
      }
 */
abstract class SinaAppEngineFileBackendList implements Iterator {
    /** @var Array */
    protected $bufferIter = array();
    protected $bufferAfter = null; // string; list items *after* this path
    protected $pos = 0; // integer
    /** @var Array */
    protected $params = array();

    /** @var SinaAppEngineFileBackend */
    protected $backend;
    protected $container; // string; container name
    protected $dir; // string; storage directory
    protected $suffixStart; // integer

    /** getList: 单次返回数量限制，默认10，最大100；limit与offset之和最大为10000，超过此范围无法列出。 **/
    /** getListByPath: 单次返回数量限制，默认100，最大1000 **/
    const PAGE_SIZE = 1000; // file listing buffer size

    /**
     * @param SinaAppEngineFileBackend $backend
     * @param string $fullCont Resolved container name
     * @param string $dir Resolved directory relative to container
     * @param array $params
     */
    public function __construct( SinaAppEngineFileBackend $backend, $fullCont, $dir, array $params ) {
        $this->backend = $backend;
        $this->container = $fullCont;
        $this->dir = $dir;
        if ( substr( $this->dir, -1 ) === '/' ) {
            $this->dir = substr( $this->dir, 0, -1 ); // remove trailing slash
        }
        if ( $this->dir == '' ) { // whole container
            $this->suffixStart = 0;
        } else { // dir within container
            $this->suffixStart = strlen( $this->dir ) + 1; // size of "path/to/dir/"
        }
        $this->params = $params;
    }

    /**
     * @see Iterator::rewind()
     * @return void
     */
    public function rewind() {
        $this->pos = 0;
        $this->bufferAfter = null;
        $this->bufferIter = $this->pageFromList(
            $this->container, $this->dir, $this->bufferAfter, self::PAGE_SIZE, $this->params
        ); // updates $this->bufferAfter
    }

    /**
     * @see Iterator::valid()
     * @return bool
     */
    public function valid() {
        if ( $this->bufferIter === null ) {
            return false; // some failure?
        } else {
            return ( current( $this->bufferIter ) !== false ); // no paths can have this value
        }
    }

    /**
     * @see Iterator::key()
     * @return integer
     */
    public function key() {
        return $this->pos;
    }

    /**
     * @see Iterator::next()
     * @return void
     */
    public function next() {
        // Advance to the next file in the page
        next( $this->bufferIter );
        ++$this->pos;
        // Check if there are no files left in this page and
        // advance to the next page if this page was not empty.
        if ( !$this->valid() && count( $this->bufferIter ) ) {
            $this->bufferIter = $this->pageFromList(
                $this->container, $this->dir, $this->bufferAfter, self::PAGE_SIZE, $this->params
            ); // updates $this->bufferAfter
        }
    }

    /**
     * Get the given list portion (page)
     *
     * @param string $container Resolved container name
     * @param string $dir Resolved path relative to container
     * @param string $after|null
     * @param integer $limit
     * @param array $params
     * @return Traversable|Array
     */
    abstract protected function pageFromList( $container, $dir, &$after, $limit, array $params );
}

/**
 * Iterator for listing directories
 */
class SinaAppEngineFileBackendDirList extends SinaAppEngineFileBackendList {
    /**
     * @see Iterator::current()
     * @return string|bool String (relative path) or false
     */
    public function current() {
        return substr( current( $this->bufferIter ), $this->suffixStart, -1 );
    }

    /**
     * @see SinaAppEngineFileBackendList::pageFromList()
     * @return Array
     */
    protected function pageFromList( $container, $dir, &$after, $limit, array $params ) {
        return $this->backend->getDirListPageInternal( $container, $dir, $after, $limit, $params );
    }
}

/**
 * Iterator for listing regular files
 */
class SinaAppEngineFileBackendFileList extends SinaAppEngineFileBackendList {
    /**
     * @see Iterator::current()
     * @return string|bool String (relative path) or false
     */
    public function current() {
        return substr( current( $this->bufferIter ), $this->suffixStart );
    }

    /**
     * @see SinaAppEngineFileBackendList::pageFromList()
     * @return Array
     */
    protected function pageFromList( $container, $dir, &$after, $limit, array $params ) {
        return $this->backend->getFileListPageInternal( $container, $dir, $after, $limit, $params );
    }
}
