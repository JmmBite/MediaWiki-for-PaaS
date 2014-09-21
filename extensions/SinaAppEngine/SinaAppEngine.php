<?php
# http://www.mediawiki.org/wiki/Version_lifecycle
# http://www.mediawiki.org/wiki/MediaWiki_1.22
# Activation : add next line to LocalSettings.php
# require_once("$IP/extensions/SinaAppEngine/SinaAppEngine.php");

if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'SinaAppEngine',
	'author'         => array( '[//sae.sina.com.cn Hallo Welt! Medienwerkstatt GmbH]', 'Jieming Mao' ),
	'version'        => '1.0.0',
	'url'            => '//www.mediawiki.org/wiki/Extension:SinaAppEngine',
	'descriptionmsg' => 'sinaappengine-desc',
);

$dir = __DIR__;
$wgExtensionMessagesFiles['SinaAppEngine'] = "{$dir}/SinaAppEngine.i18n.php";
$wgAutoloadClasses['SinaAppEngineFileBackend'] = "{$dir}/SinaAppEngineFileBackend.php";
$wgAutoloadClasses['SinaAppEngineLocalRepo'] = "{$dir}/SinaAppEngineLocalRepo.php";

/************************************************************************//**
 * @name   Database settings
 */
## Database settings
$wgDBtype     = 'mysql';
$wgDBserver   = SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT;
$wgDBname     = SAE_MYSQL_DB;
$wgDBuser     = SAE_MYSQL_USER;
$wgDBpassword = SAE_MYSQL_PASS;
#$wgDBprefix   = 'wiki_';
$wgDBTableOptions = 'ENGINE=MyISAM, DEFAULT CHARSET=utf8';
## LoadBalancer settings
$wgDBservers = array(
     array(
         'host'     => SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT,
         'dbname'   => SAE_MYSQL_DB,
         'user'     => SAE_MYSQL_USER,
         'password' => SAE_MYSQL_PASS,
         'type'     => $wgDBtype,
         'flags'    => DBO_TRX, //DBO_DEFAULT,
         'load'     => 0,
         'fakeSlaveLag' => 0,
         'fakeMaster'   => true,
     ),
     array(
         'host'     => SAE_MYSQL_HOST_S.':'.SAE_MYSQL_PORT,
         'dbname'   => SAE_MYSQL_DB,
         'user'     => SAE_MYSQL_USER,
         'password' => SAE_MYSQL_PASS,
         'type'     => $wgDBtype,
         'flags'    => DBO_TRX, //DBO_DEFAULT,
         'load'     => 1,
         'fakeSlaveLag' => 0,
         'fakeMaster'   => true,
     ),
);

/************************************************************************//**
 * @name   Files and file uploads
 */
$wgEnableUploads = true;
$wgTmpDirectory = $_ENV["TMPDIR"];
#$wgFileExtensions = array( 'png', 'gif', 'jpg', 'jpeg' );

## Uploads
define( 'SAESTOR_DOMAIN', 'mediawiki' );
$wgUploadPath      = '/images';
$wgUploadDirectory = "saestor://" . SAESTOR_DOMAIN . $wgUploadPath;
$wgUploadBaseUrl   = "http://{$_SERVER['HTTP_APPNAME']}-" . SAESTOR_DOMAIN . ".stor.sinaapp.com{$wgUploadPath}";
# divide your images directory into many subdirectories, for improved performance
#$wgHashedUploadDirectory = true;

$wgFileBackends[] = array(
    #'name'         => 'localSinaAppEngine',
    'name'         => SAESTOR_DOMAIN,
    'class'        => 'SinaAppEngineFileBackend',
    'lockManager'  => 'nullLockManager',
);
$wgLocalFileRepo = array (
    # Properties required for all repos:
    'class'             => 'SinaAppEngineLocalRepo',
    'name'              => 'local',
    'backend'           => SAESTOR_DOMAIN,
    # For most core repos:
    'zones'             => array(
        'public'  => array( 'container' => 'public_container',  'directory' => 'public', 'url' => "{$wgUploadBaseUrl}/public", ),
        'thumb'   => array( 'container' => 'thumb_container',   'directory' => 'thumb', 'url' => "{$wgUploadBaseUrl}/thumb", ),
        'temp'    => array( 'container' => 'temp_container',    'directory' => 'temp', 'url' => "{$wgUploadBaseUrl}/temp", ),
        'deleted' => array( 'container' => 'deleted_container', 'directory' => 'deleted', 'url' => "{$wgUploadBaseUrl}/deleted", ),
    ),
    'url'               => $wgUploadBaseUrl ? $wgUploadBaseUrl : $wgUploadPath,
    'hashLevels'        => $wgHashedUploadDirectory ? 2 : 0,
    'deletedHashLevels' => $wgHashedUploadDirectory ? 3 : 0,
);

/************************************************************************//**
 * @name   Cache settings
 */
## Memcached settings
$wgMainCacheType = CACHE_MEMCACHED;
$wgMemCachedServers = array( '' );
$wgMessageCacheType = $wgMainCacheType;
#$wgMemCachedPersistent = false;
#$wgMemCachedTimeout = 500000;

/************************************************************************//**
 * @name   Proxy scanner settings
 */
$wgSecretKey = SAE_SECRETKEY;
$wgUpgradeKey = SAE_ACCESSKEY;

/*************************************************************************//**
 * @name   Output format and skin settings
 */
$wgFooterIcons['copyright']['copyright'] = array(
    'src' => "{$wgStylePath}/common/images/cc-by-nc-sa.png",
    'url' => '//creativecommons.org/licenses/by-nc-sa/3.0/',
    'alt' => 'Creative Commons Attribution Non-Commercial Share Alike',
);
$wgFooterIcons['poweredby']['sae'] = array(
    'src' => 'http://static.sae.sina.com.cn/image/poweredby/poweredby.png',
    'url' => 'http://sae.sina.com.cn/',
    'alt' => 'Powered by Sina App Engine',
);