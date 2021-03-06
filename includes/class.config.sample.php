<?PHP

    // The Config class provides a single object to store your application's settings.
    // Define your settings as public members. (We've already setup the standard options
    // required for the Database and Auth classes.) Then, assign values to those settings
    // inside the "location" functions. This allows you to have different configuration
    // options depending on the server environment you're running on. Ex: local, staging,
    // and production.

	// I highly reccomend NOT sending emails through your own server. They'll almost
	// always get classified as spam. I use http://postmarkapp.com/ and have been
	// extremely happy with the results. -Tyler
	define('POSTMARKAPP_API_KEY', '');
	define('POSTMARKAPP_MAIL_FROM_ADDRESS', '');
	define('POSTMARKAPP_MAIL_FROM_NAME', '');
	
	
	
	define('VERSION_STATUS_PRODUCTION', 1);
	define('VERSION_STATUS_BETA', 2);
	define('VERSION_STATUS_TEST', 3);
	
    define('HTTP_SITE_NAME', !empty($_SERVER['SERVER_NAME']) ? ( (!empty($_SERVER['HTTPS']) ? 'https' : 'http' ) . '://' . $_SERVER['SERVER_NAME']  ) : '');
	


    class Config
    {
        // Singleton object. Leave $me alone.
        private static $me;

        // Add your server hostnames to the appropriate arrays. ($_SERVER['HTTP_HOST'])
        private $productionServers = array('yourdomain.com');
        private $stagingServers    = array();
        private $localServers      = array('localhost');

        // Standard Config Options...

        // ...For Auth Class
        public $authDomain;         // Domain to set for the cookie
        public $authSalt;           // Can be any random string of characters
        public $useHashedPasswords; // Store hashed passwords in database? (versus plain-text)

        // ...For Database Class
        public $dbHost;       // Database server
        public $dbName;       // Database name
        public $dbUsername;   // Database username
        public $dbPassword;   // Database password
        public $dbDieOnError; // What do do on a database error (see class.database.php for details)

        // Add your config options here...
        public $useDBSessions; // Set to true to store sessions in the database

        // Singleton constructor
        private function __construct()
        {
            $this->everywhere();

            $i_am_here = $this->whereAmI();

            if('production' == $i_am_here)
                $this->production();
            elseif('staging' == $i_am_here)
                $this->staging();
            elseif('local' == $i_am_here)
                $this->local();
            elseif('shell' == $i_am_here)
                $this->shell();
            else
                die('<h1>Where am I?</h1> <p>You need to setup your server names in <code>class.config.php</code></p>
                     <p><code>$_SERVER[\'HTTP_HOST\']</code> reported <code>' . $_SERVER['HTTP_HOST'] . '</code></p>');
        }

        // Get Singleton object
        public static function getConfig()
        {
            if(is_null(self::$me))
                self::$me = new Config();
            return self::$me;
        }

        // Allow access to config settings statically.
        // Ex: Config::get('some_value')
        public static function get($key)
        {
            return self::$me->$key;
        }

        // Add code to be run on all servers
        private function everywhere()
        {
            // Store sesions in the database?
            $this->useDBSessions = true;

            // Settings for the Auth class
            $this->authDomain         = $_SERVER['HTTP_HOST'];
            $this->useHashedPasswords = false;
            $this->authSalt           = ''; // Pick any random string of characters
            
            // BEGIN adib 7-Apr-2010 10:22
            // incoming staging folder to upload new file packages.
            // users will have an option to select files from this folder instead of uploading them
            // via the web browser.
            $this->uploadFolder	= '';
            // variables downloadBaseFolder and downloadBaseURL are used as a fallback file storage 
            // when Amazon S3 is not configured for the application.
            $this->downloadBaseFolder = '';		// base file name for file downloads.  Should be under the web root folder.
            $this->downloadBaseURL	  = '';		// the URL base location of the above file.
            // END adib 7-Apr-2010 10:22
            
        }

        // Add code/variables to be run only on production servers
        private function production()
        {
            ini_set('display_errors', '0');

            define('WEB_ROOT', '/');

            $this->dbHost       = '';
            $this->dbName       = '';
            $this->dbUsername   = '';
            $this->dbPassword   = '';
            $this->dbDieOnError = false;
        }

        // Add code/variables to be run only on staging servers
        private function staging()
        {
            ini_set('display_errors', '1');
            ini_set('error_reporting', E_ALL);

            define('WEB_ROOT', '');

            $this->dbHost       = '';
            $this->dbName       = '';
            $this->dbUsername   = '';
            $this->dbPassword   = '';
            $this->dbDieOnError = false;
        }

        // Add code/variables to be run only on local (testing) servers
        private function local()
        {
            ini_set('display_errors', '1');
            ini_set('error_reporting', E_ALL);

            define('WEB_ROOT', '');

            $this->dbHost       = '';
            $this->dbName       = '';
            $this->dbUsername   = '';
            $this->dbPassword   = '';
            $this->dbDieOnError = true;
        }

        // Add code/variables to be run only on when script is launched from the shell
        private function shell()
        {
            ini_set('display_errors', '1');
            ini_set('error_reporting', E_ALL);

            define('WEB_ROOT', '');

            $this->dbHost       = '';
            $this->dbName       = '';
            $this->dbUsername   = '';
            $this->dbPassword   = '';
            $this->dbDieOnError = true;
        }

        public function whereAmI()
        {
            if(in_array($_SERVER['HTTP_HOST'], $this->productionServers))
                return 'production';
            elseif(in_array($_SERVER['HTTP_HOST'], $this->stagingServers))
                return 'staging';
            elseif(in_array($_SERVER['HTTP_HOST'], $this->localServers))
                return 'local';
            elseif(isset($_ENV['SHELL']))
                return 'shell';
            else
                return false;
        }
    }
