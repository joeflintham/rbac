<?php

    define('ERRMSG_DEFAULT_ERRMSG', "Apologies, there was an error...");
    define('ERRMSG_USERNAME_TAKEN', "The requested username is already in use, sorry.");
    define('ERRMSG_USER_NOT_DELETED', "It was not possible to delete this user, sorry");
    define('ERRMSG_PASSWORD_NOT_CHANGED', "It was not possible to change the password for this user, sorry");
    define('ERRMSG_USER_NOT_EDITED', "It was not possible to store that user's new details, sorry");
    define('ERRMSG_USERNAME_NOT_VALID', "Please supply a username for this user");
    define('ERRMSG_USERNAME_HAS_INVALID_CHARACTERS', "Please supply a user name without invalid characters (alpha-numeric and underscore only)");
    define('ERRMSG_USERPASSWORD_NOT_VALID', "Please supply a password for this user");
    define('ERRMSG_USERDISPLAYNAME_NOT_VALID', "Please supply a display name for this user");
    define('ERRMSG_USERDISPLAYNAME_HAS_INVALID_CHARACTERS', "Please specify a display-name without invalid characters for this user (alpha-numeric, underscore and hyphen only)");
    define('ERRMSG_USERTYPE_NOT_VALID', "Please specify a type for this user");
    define('ERRMSG_USER_HAS_NO_AUTHORITY_TO_PROMOTE', "You can't give another user a higher permission level than your own, sorry. ");
    define('ERRMSG_USER_HAS_EQUAL_AUTHORITY_IN_PERMISSION_EDITING', "You can't alter the permission level of someone with the same permissions as your own, sorry.");
    define('ERRMSG_USER_PERMISSION_NOT_ESTABLISHED', "It was not possible to establish the existing permission level of this user, sorry.");
    define('ERRMSG_NEW_PASSWORD_NOT_VALID', "Please provide a new password for this user");
    define('ERRMSG_USER_PASSWORD_MATCH_NOT_VALID', "The new password confirmation did not match");
    define('ERRMSG_USER_DELETION_NOT_AUTHORISED', "You can't delete users with the same permission level as your own account, sorry.");
    define('ERRMSG_USER_DELETION_TARGET_NOT_FOUND', "It was not possible to identify the user you want to delete, sorry.");

    class ErrorHandler {
        
        public $caller;
        public $messageList;
        public static $errorHandlers;
          
        public function __construct($obj, $msglist = array()){
            
            self::$errorHandlers = array();
            self::register($this);
            
            $this->caller = $obj;    
            if (!isset($msglist)){ $msglist = array(ERRMSG_DEFAULT_ERRMSG); }
            $this->messageList = $msglist;
            
            
        }
        
        public function register($obj){
            self::$errorHandlers[] = get_class($obj);
        }
    }

?>