<?php

    define('MYSQLHOST',             $mysqlHost);
    define('MYSQLUSER',             $mysqlUser);
    define('MYSQLPASSWORD',         $mysqlPassword);
    define('MYSQLDBNAME',           $mysqlDBName);

    define('SITEROOT',              $siteroot);

    require_once (__DIR__ . "/Permissions.php");
    require_once (__DIR__ . "/ErrorHandler.php");
    require_once (__DIR__ . "/../DB/DB_Mysql.php");
    
    class User {
    
        public $authenticated;
        
        private static $DB; 
        private static $instance; 
        private static $sanity;
                
        private $user_id;
        private $user_name;
        private $user_password;
        private $user_displayName;
        private $user_type; 
        private $user_lastLogin; 
        
        private $permissions;

        public function __construct(){
        
            self::checkSanity();
            
            if (!self::$sanity){
    
                if (!(class_exists("DB_Mysql"))){
                    die("No Database Connection");
                }
                            
                if (!in_array("users", self::$DB->tables)){
                
                    $sql = "
                    
                        CREATE TABLE IF NOT EXISTS `users` (
                          `user_id` int(10) NOT NULL AUTO_INCREMENT,
                          `user_name` varchar(20) NOT NULL,
                          `user_password` varchar(50) NOT NULL,
                          `user_displayName` varChar(40) NOT NULL,
                          `user_type` set('guest','member','contributor','admin','superuser') NOT NULL,
                          `user_lastLogin` int(10) NOT NULL,
                          `user_active` tinyint(1) NOT NULL,
                          PRIMARY KEY (`user_id`)
                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
                    ";

		    if ($query = self::$DB->DB_Execute($sql)){
            		
                    $sqlCreateUser = "

                        INSERT INTO users 
                            (user_name, user_password, user_displayName, user_type)
                        VALUES
                            ('nobody','365ec17a675f3273bc16c74761ad83f2cf07c59a','nobody','nobody');
                        
                        ";
                        
                    self::$DB->DB_Execute($sqlCreateUser);

                    self::$sanity = true;
                    $this->authenticate();
            	    
		    }	
                }
            }
        }
        
        public static function setupUser(){
        
    		if (!(isset(self::$instance))){
                self::$instance = new User();
                self::$instance->authenticate();
            }
            return self::$instance;
        }

        public static function getUser(){
            return self::$instance;
        }
        
        public static function createUser($userData = array()){

            self::checkSanity();

            $validData = self::userDataValidate($userData);
            
            if (is_object($validData) && in_array(get_class($validData), ErrorHandler::$errorHandlers)){
                return $validData; // return error object
            }
            
            $user_name = $userData["user_name"];
            $user_password = sha1($userData["user_password"]);
            $user_displayName = $userData["user_displayName"];
            $user_type = $userData["user_type"];
        
            $sqlCheckDupes = "SELECT * FROM users WHERE user_name = '%s'";
            $query = sprintf($sqlCheckDupes, DB_Mysql::cleanse($user_name));
            $result = self::$DB->DB_Execute($query);

            if (self::$DB->foundRows > 0){
                return new ErrorHandler(self::$instance, array(ERRMSG_USERNAME_TAKEN)); 
            }
            
            $sqlCreateUser = 
            
                "
                INSERT INTO users 
                    (user_name, user_password, user_displayName, user_type)
                VALUES
                    ('%s','%s','%s','%s');
                ";
            
            $query = sprintf($sqlCreateUser, $user_name, $user_password, $user_displayName, $user_type);
            $result = self::$DB->DB_Execute($query);
            
            return self::$DB->ID;
            
        }
        
        public static function deleteUser($userData = array()){

            self::checkSanity();

            $validData = self::userDataValidate($userData, "delete");
            
            if (is_object($validData) && in_array(get_class($validData), ErrorHandler::$errorHandlers)){
                return $validData; // return error object
            }
            
            $username = $userData["user_name"];
        
            $sqlCheckExists = "SELECT * FROM users WHERE user_name = '%s'";
            $query = sprintf($sqlCheckExists, DB_Mysql::cleanse($username));
            $result = self::$DB->DB_Execute($query);

            if (self::$DB->foundRows > 0){
                
                $sqlDeleteUser = 
                
                    "
                    DELETE FROM users 
                    WHERE
                        user_name = '%s';
                    ";
                
                $query = sprintf($sqlDeleteUser, DB_Mysql::cleanse($username));
                $result = self::$DB->DB_Execute($query);
                
                if (self::$DB->errorNo){
                    return new ErrorHandler(self::$instance, array(ERRMSG_USER_NOT_DELETED));
                }

                return true;
    
            } else {
                return new ErrorHandler(self::$instance, array(ERRMSG_USERCREDENTIALS_NOT_FOUND)); 
            }
            
        }
        
        public static function changePassword($userData = array()){
        
            self::checkSanity();
        
            $validData = self::userDataValidate($userData, "changepassword");
            
            if (is_object($validData) && in_array(get_class($validData), ErrorHandler::$errorHandlers)){
                return $validData; // return error object
            }

            $username = $userData["user_name"];
            $newpassword = sha1($userData["user_new_password"]); // we only deal with hashes!
        
            $sqlCheckExists = "SELECT * FROM users WHERE user_name = '%s'";
            $query = sprintf($sqlCheckExists, DB_Mysql::cleanse($username));
            $result = self::$DB->DB_Execute($query);

            if (self::$DB->foundRows > 0){
                
                $sqlChangePassword = 
                
                    "
                    UPDATE users 
                    SET user_password = '%s'
                    WHERE user_name = '%s';
                    ";
                
                $query = sprintf($sqlChangePassword, DB_Mysql::cleanse($newpassword), DB_Mysql::cleanse($username));

                $result = self::$DB->DB_Execute($query);

                if (self::$DB->errorNo){
                    return new ErrorHandler(self::$instance, array(ERRMSG_PASSWORD_NOT_CHANGED));
                }

                return true;
                
            } else {
                return new ErrorHandler(self::$instance, array(ERRMSG_USERCREDENTIALS_NOT_FOUND)); 
            }
            
        }
        
        public static function editUser($userData = array()){
        
            self::checkSanity();
        
            $validData = self::userDataValidate($userData, "edit");
            
            if (is_object($validData) && in_array(get_class($validData), ErrorHandler::$errorHandlers)){
                return $validData; // return error object
            }

            $user_name = $userData["user_name"];
            $user_displayName = $userData["user_displayName"];
            $user_type = $userData["user_type"];
        
            $sqlCheckExists = "SELECT * FROM users WHERE user_name = '%s'";
            $query = sprintf($sqlCheckExists, DB_Mysql::cleanse($user_name));
            $result = self::$DB->DB_Execute($query);

            if (self::$DB->foundRows > 0){

                $sqlEditUser = 
                
                    "
                    UPDATE users 
                    SET 
                        user_displayName = '%s', 
                        user_type = '%s'
                    WHERE 
                        user_name = '%s';
                    ";
                
                $query = sprintf($sqlEditUser, $user_displayName, $user_type, $user_name);
                $result = self::$DB->DB_Execute($query);

                if (self::$DB->errorNo){
                    return new ErrorHandler(self::$instance, array(ERRMSG_USER_NOT_EDITED));
                }

                return true;

            } else {
                return new ErrorHandler(self::$instance, array(ERRMSG_USERCREDENTIALS_NOT_FOUND)); 
            }
            
            
        }
        
        public static function getUserTypes(){
            return array(
                'nobody',
                'guest',
                'member',
                'contributor',
                'admin',
                'superuser'
            );
        }
                
        public static function getUserData($user_id = ''){
            if (!($user_id)) { return false; }
            $sql = "SELECT * FROM users WHERE user_id = '%s'";
            $query = sprintf($sql, DB_Mysql::cleanse($user_id, "int"));
            $result = self::$DB->DB_ExecuteAndProcess($query);
            if (is_array($result) && sizeof($result) > 0){
                return $result[0];        
            }
        }
                
        /* public functions */

        public function hasPermission($permRef){
            if (!$permRef) return false;
            if (is_object($this->permissions)){
                return $this->permissions->hasPermission($permRef);
            }
        }

        /* private functions */

        private static function checkSanity(){

	    /* check we have a sane environment */        
            if (!(class_exists("DB_Mysql"))){ self::$sanity = false; return; }
            self::$DB = DB_Mysql::create(MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDBNAME);
            if (!(in_array("users", self::$DB->tables))){ self::$sanity = false; return; }
            self::$sanity = true;

        }

        private static function outranksByType($type){
	    /* return a boolean reflecting whether the current user outranks the target user's type */
            $targetNumericalLevel = Permissions::getLevel($type);
            $currentNumericalLevel = Permissions::getLevel(User::getUser()->user_type);
            return ($currentNumericalLevel >= $targetNumericalLevel); 
        }
        
        private static function equalByType($type){
	    /* return a boolean reflecting whether the current user of equal rank to the target user's type */
            $targetNumericalLevel = Permissions::getLevel($type);
            $currentNumericalLevel = Permissions::getLevel(User::getUser()->user_type);
            return ($currentNumericalLevel = $targetNumericalLevel); 
        }
        
        private static function outrankedByType($type){
	    /* return a boolean reflecting whether the current user is outranked by the target user's type */
            $targetNumericalLevel = Permissions::getLevel($type);
            $currentNumericalLevel = Permissions::getLevel(User::getUser()->user_type);
            return ($currentNumericalLevel < $targetNumericalLevel); 
        }
        
        private static function userDataValidate($userData = array(), $validationType = "create"){
        
            /* valid user_name is bare minimum for any user action */
            
            // check user_name is supplied            
            if (!isset($userData["user_name"]) || (!$userData["user_name"])) { 
	        $throwError = true; $msglist[] = ERRMSG_USERNAME_NOT_VALID; 
	    }
            
            // check user_name is valid
            if (preg_match("/[^a-zA-Z0-9_-]+/", $userData["user_name"])) { 
	        $throwError = true; $msglist[] = ERRMSG_USERNAME_HAS_INVALID_CHARACTERS; 
	    }

            /* valid user_password is required when creating user */
            if ($validationType == "create"){
                if (!isset($userData["user_password"]) || (!$userData["user_password"])) { 
		    $throwError = true; $msglist[] = ERRMSG_USERPASSWORD_NOT_VALID; 
		}
            }
            
	    /* validate attempts to create or edit a user */
            if ($validationType == "create" || $validationType == "edit"){

	        /* valid user_displayName & user_type are necessary for user details / account edit */
                
		if (!isset($userData["user_displayName"]) || (!$userData["user_displayName"])) { 
		    $throwError = true; $msglist[] = ERRMSG_USERDISPLAYNAME_NOT_VALID; 
		}
    
                if (isset($userData["user_displayName"]) && preg_match("/[^a-zA-Z0-9_-\s]+/", $userData["user_displayName"])) { 
		    $throwError = true; $msglist[] = ERRMSG_USERDISPLAYNAME_HAS_INVALID_CHARACTERS; 
		}
    
                if (!isset($userData["user_type"]) || (!$userData["user_type"])) { 
		    $throwError = true; $msglist[] = ERRMSG_USERTYPE_NOT_VALID; 
		}
    
                if (isset($userData["user_type"]) && (!in_array($userData["user_type"], self::getUserTypes()))) { 
		    $throwError = true; $msglist[] = ERRMSG_USERTYPE_NOT_VALID; 
		}
                
		/* check the current user has permission to perform this action */
                if (isset($userData["user_name"]) && isset($userData["user_type"]) && in_array($userData["user_type"], self::getUserTypes())) {

                    if (!User::outranksByType($userData["user_type"])) {
                        $throwError = true; $msglist[] = ERRMSG_USER_HAS_NO_AUTHORITY_TO_PROMOTE;
                    }
                }
            }
            
	    /* validation of user edit actions start with a check whether the current user has permission to do so */
            if ($validationType == "edit"){
            
                $sql = "SELECT * FROM users WHERE user_name = '%s'";
                $query = sprintf($sql, DB_Mysql::cleanse($userData["user_name"]));

                $result = self::$DB->DB_ExecuteAndProcess($query);
                if (is_array($result) && sizeof($result) > 0){
                    $current_user_type = $result[0]["user_type"];
                    
                    if (($userData["user_type"] != $current_user_type) && $current_user_type ==  User::getUser()->user_type){
                        $throwError = true; $msglist[] = ERRMSG_USER_HAS_EQUAL_AUTHORITY_IN_PERMISSION_EDITING;
                    }

                } else {
                    $throwError = true; $msglist[] = ERRMSG_USER_PERMISSION_NOT_ESTABLISHED;
                }
            }


            /* valid user_new_password & user_new_password_repeat are necessary for user password change */

            if ($validationType == "changepassword"){
            
                if (
                    !(isset($userData["user_new_password"]) && $userData["user_new_password"])
                        || 
                    !(isset($userData["user_new_password_repeat"]) && $userData["user_new_password_repeat"])
                ) { 
                    $throwError = true; $msglist[] = ERRMSG_NEW_PASSWORD_NOT_VALID; 
                }

                if (
                    (isset($userData["user_new_password"]) && (isset($userData["user_new_password_repeat"])))
                    &&
                    ($userData["user_new_password"] != $userData["user_new_password_repeat"])
                ) { 
		    $throwError = true; $msglist[] = ERRMSG_USER_PASSWORD_MATCH_NOT_VALID; 
		}
            }
            
            if ($validationType == "delete"){
            
                if (isset($userData["user_name"])) {

                    $sql = "SELECT * FROM users WHERE user_name = '%s'";
                    $query = sprintf($sql, DB_Mysql::cleanse($userData["user_name"]));

                    $result = self::$DB->DB_ExecuteAndProcess($query);
                    if (is_array($result) && sizeof($result) > 0){

                        $current_user_type = $result[0]["user_type"];
            
                        if (User::outranksByType($current_user_type)) {
                            $throwError = true; $msglist[] = ERRMSG_USER_DELETION_NOT_AUTHORISED;
                        }        
                        
                    } else {
                        $throwError = true; $msglist[] = ERRMSG_USER_DELETION_TARGET_NOT_FOUND;
                    }
                }
            }
            
            if (isset($throwError) && $throwError){
                return new ErrorHandler(self::$instance, $msglist); 
            }

	    /* if we got this far, then we have been successful */
            return true;
        }

        private function authenticate(){
            
            if (!self::$sanity) { return false; }
            
            $username = ""; $password = "";
            
            // look for credentials in get data (i.e. a hacky login attempt)
            if (isset($_GET["user_name"]) && $_GET["user_name"] != ''){
                $username = $_GET["user_name"];
                $password = sha1($_GET["user_password"]);  // we only deal with password hashes
                
            // look for credentials in post data (i.e. a login attempt)
            } else if (isset($_POST["user_name"]) && $_POST["user_name"] != ''){
                $username = $_POST["user_name"];
                $password = sha1($_POST["user_password"]);  // we only deal with password hashes
            
            // look for credentials in cookie data (i.e. already logged in, cookies operational)
            } else if (isset($_COOKIE["user_name"]) && $_COOKIE["user_name"] != ''){
                $username = $_COOKIE["user_name"];
                $password = $_COOKIE["user_password"];      // should only ever be a password hash
                          
            // look for credentials in session data (i.e. already logged in)
            } else if (isset($_SESSION["user_name"]) && $_SESSION["user_name"] != ''){
                $username = $_SESSION["user_name"];
                $password = $_SESSION["user_password"];      // should only ever be a password hash
                
            }
            
            // authenticate against database
            if ($username && $password){
                $result = $this->lookUpCredentials($username, $password);
                if (is_array($result) && sizeof($result) == 1){
                
                    // authentic user matched in database
                    $this->login($result[0]);
                    if (isset($_GET["logout"]) || isset($_POST["logout"])){
                        $this->logout($result[0]);
                        return false;
                    }
                    $this->loadPermissions();
                }
            } else {
                return false;
            }
        }
            
        private function lookUpCredentials($username = '', $password = ''){ // username and password hash
            if (!($username && $password)) { return false; }
            $sql = "SELECT * FROM users WHERE user_name = '%s' AND user_password = '%s'";
            $query = sprintf($sql, DB_Mysql::cleanse($username), DB_Mysql::cleanse($password));
            $result = self::$DB->DB_ExecuteAndProcess($query);
            return $result;
        }
        
        private function login($userData){

            $time = time();
            $userData["user_lastLogin"] = $time;
            
            while (list($key, $value) = each($userData)){
                $this->setUserCookie($key, $value, 30, SITEROOT);
                $_SESSION[$key] = $value;
                $this->$key = $value;
            }
        
            $sql = "UPDATE users SET user_lastLogin = '%s', user_active = '1' WHERE user_id = '%s';";
            $query = sprintf($sql, DB_Mysql::cleanse($userData["user_lastLogin"], "timestamp"), DB_Mysql::cleanse($userData["user_id"], "int"));
            $result = self::$DB->DB_Execute($query);

            $this->authenticated = true;
        }
        
        private function loadPermissions(){
            if (!(class_exists("Permissions"))){ return false; }
            $this->permissions = Permissions::initialise($this->user_type);
        }
        
        private function unloadPermissions(){
            if (isset($this->permissions) && is_object($this->permissions)){
                $this->permissions->destroy();
            }
        }

        private function logout($userData){
        
            while (list($key, $value) = each($userData)){
                $this->setUserCookie($key, null, 30, SITEROOT);
                $_SESSION[$key] = null;
            }
        
            $sql = "UPDATE users SET user_active = '0' WHERE user_id = '%s';";
            $query = sprintf($sql, DB_Mysql::cleanse($userData["user_id"], "int"));
            $result = self::$DB->DB_Execute($query);

            $this->authenticated = false;
            $this->unloadPermissions();
        }
        
        private function setUserCookie($key, $value, $days = 30){

            $cookieExpirationLimit = 60 * 60 * 24 * $days + time(); // a month 
            setcookie($key, $value, $cookieExpirationLimit, SITEROOT);
        
        }

    }
?>