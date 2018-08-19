<?PHP
# atomic: don't change timezone
//	date_default_timezone_set('America/Chicago');


    // Application flag
    define('SPF', true);
	define('DEFAULT_IPN_URL', 'https://www.paypal.com/cgi-bin/webscr?');
	define('SANDBOX_IPN_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr?');

    // Determine our absolute document root
    define('DOC_ROOT', realpath(dirname(__FILE__) . '/../'));

    define('LOCAL_UPLOAD_PATH', DOC_ROOT . '/shine_uploads');


    // Global include files
	require_once DOC_ROOT . '/includes/class.config.php';
    require DOC_ROOT . '/includes/functions.inc.php'; // __autoload() is contained in this file
    require DOC_ROOT . '/includes/class.dbobject.php';
    require DOC_ROOT . '/includes/class.objects.php';
    require DOC_ROOT . '/includes/markdown.inc.php';
    require DOC_ROOT . '/includes/Postmark.php';


    // Fix magic quotes
    if(get_magic_quotes_gpc())
    {
        $_POST    = fix_slashes($_POST);
        $_GET     = fix_slashes($_GET);
        $_REQUEST = fix_slashes($_REQUEST);
        $_COOKIE  = fix_slashes($_COOKIE);
    }

    // Load our config settings
    $Config = Config::getConfig();

    // Store session info in the database?
//    if($Config->useDBSessions === true)
//        DBSession::register();

    // Initialize our session
	session_name('spfs');
    session_start();

    // Initialize current user
    $Auth = Auth::getAuth();

    // Object for tracking and displaying error messages
    $Error = Error::getError();

    $nav = '';
    
	/**
	 * Create a list of credential sets that can be used with the SDK.
	 */
	function setCFconfig($app)
	{
		CFCredentials::set(array(
		
			// Credentials for the development environment.
			'development' => array(
		
				// Amazon Web Services Key. Found in the AWS Security Credentials. You can also pass
				// this value as the first parameter to a service constructor.
				'key' => $app->s3key,
		
				// Amazon Web Services Secret Key. Found in the AWS Security Credentials. You can also
				// pass this value as the second parameter to a service constructor.
				'secret' => $app->s3pkey,
		
				// This option allows you to configure a preferred storage type to use for caching by
				// default. This can be changed later using the set_cache_config() method.
				//
				// Valid values are: `apc`, `xcache`, or a file system path such as `./cache` or
				// `/tmp/cache/`.
				'default_cache_config' => '',
		
				// Determines which Cerificate Authority file to use.
				//
				// A value of boolean `false` will use the Certificate Authority file available on the
				// system. A value of boolean `true` will use the Certificate Authority provided by the
				// SDK. Passing a file system path to a Certificate Authority file (chmodded to `0755`)
				// will use that.
				//
				// Leave this set to `false` if you're not sure.
				'certificate_authority' => false
			),
		
			// Specify a default credential set to use if there are more than one.
			'@default' => 'development'
		));
	}