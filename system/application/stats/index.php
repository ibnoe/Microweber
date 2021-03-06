<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: index.php 4444 2011-04-14 02:24:19Z vipsoft $
 *
 * @package Piwik
 */

if(file_exists('bootstrap.php'))
{
	require_once 'bootstrap.php';
}

error_reporting(E_ALL|E_NOTICE);
@ini_set('display_errors', (!defined('PIWIK_DISPLAY_ERRORS') || PIWIK_DISPLAY_ERRORS) ? 1 : 0);
@ini_set('xdebug.show_exception_trace', 0);
@ini_set('magic_quotes_runtime', 0);

define('PIWIK_DOCUMENT_ROOT', dirname(__FILE__)=='/'?'':dirname(__FILE__));
if(!defined('PIWIK_USER_PATH'))
{
	define('PIWIK_USER_PATH', PIWIK_DOCUMENT_ROOT);
}
if(!defined('PIWIK_INCLUDE_PATH'))
{
	define('PIWIK_INCLUDE_PATH', PIWIK_DOCUMENT_ROOT);
}

require_once PIWIK_INCLUDE_PATH . '/libs/upgradephp/upgrade.php';
require_once PIWIK_INCLUDE_PATH . '/core/testMinimumPhpVersion.php';

// NOTE: the code above this comment must be PHP4 compatible

session_cache_limiter('nocache');
@date_default_timezone_set('UTC');
require_once PIWIK_INCLUDE_PATH .'/core/Loader.php';

if(!defined('PIWIK_ENABLE_SESSION_START') || PIWIK_ENABLE_SESSION_START)
{
	Piwik_Session::start();
}

if(!defined('PIWIK_ENABLE_ERROR_HANDLER') || PIWIK_ENABLE_ERROR_HANDLER)
{
	require_once PIWIK_INCLUDE_PATH .'/core/ErrorHandler.php';
	require_once PIWIK_INCLUDE_PATH .'/core/ExceptionHandler.php';
	set_error_handler('Piwik_ErrorHandler');
	set_exception_handler('Piwik_ExceptionHandler');
}

if(!defined('PIWIK_ENABLE_DISPATCH') || PIWIK_ENABLE_DISPATCH)
{
	$controller = Piwik_FrontController::getInstance();
	$controller->init();
	$controller->dispatch();
}
