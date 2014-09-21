<?php
class SinaAppEngineLocalRepo extends LocalRepo {
	/**
	 * @see FileRepo::getZoneLocation
	 * The the storage container and base path of a zone
	 *
	 * @param $zone string
	 * @return Array (container, base path) or (null, null)
	 */
	protected function getZoneLocation( $zone ) {
		if ( !isset( $this->zones[$zone] ) ) {
			return array( null, null ); // bogus
		}
		return array( $this->zones[$zone]['container'], $this->zones[$zone]['directory'] );
	}

	/**
	 * @see FileRepo::getZonePath
	 * Get the storage path corresponding to one of the zones
	 *
	 * @param $zone string
	 * @return string|null Returns null if the zone is not defined
	 */
	public function getZonePath( $zone ) {
		//global $wgUploadDirectory;
		//return $wgUploadDirectory;
		list( $container, $base ) = $this->getZoneLocation( $zone );
		if ( $container === null || $base === null ) {
			return null;
		}
		$backendName = $this->backend->getName();
		if ( $base != '' ) { // may not be set
			$base = "/{$base}";
		}
		//var_dump( $zone );
		//var_dump( __METHOD__ );
		return "mwstore://$backendName/{$container}{$base}";
	}

	/**
     * @see FileRepo::initDirectory
	 * Creates a directory with the appropriate zone permissions.
	 * Callers are responsible for doing read-only and "writable repo" checks.
	 *
	 * @param string $dir Virtual URL (or storage path) of directory to clean
	 * @return Status
	 */
	//public function initDirectory( $dir ) {
	//	return Status::newGood();
	//}
}