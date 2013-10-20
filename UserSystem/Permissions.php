<?php

    class Permissions {
        
        private static $DB;
        
        private static $instance;
        
        private static $levelsLookupTable;
        private $permissionsLookupTable;

        private $userType;
        private $userPermissionsTable;
        
        public function __construct($type = "guest"){
            
            $this->userType = $type;
            
            $this->checkSanity();
                    
            $this->populateLookupTables();
            
            if (!$this->sanity){
    
                if (!(class_exists("DB_Mysql"))){
                    die("No Database Connection");
                }

                if (!in_array("permissions", self::$DB->tables)){

                    $sql = "
                    
                        CREATE TABLE IF NOT EXISTS `permissions` (
                          `permission_id` int(10) NOT NULL AUTO_INCREMENT,
                          `permission_description` varchar(255) NOT NULL,
                          `permission_reference` varchar(50) NOT NULL,
                          `permission_level` set('nobody','guest','member','admin','superuser','god') NOT NULL,
                          PRIMARY KEY (`permission_id`)
                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
                        
                    ";

            		if ($query = self::$DB->DB_Execute($sql)){
            		  
                        $sqlCreatePermission = "
                        
                        INSERT INTO permissions 
                            (permission_description, permission_reference, permission_level)
                        VALUES
                            ('Dummy permission','dummy_permission','guest');

                        ";
                        
                        self::$DB->DB_Execute($sqlCreatePermission);
            		
                        $this->sanity = true;
            		}
            		
            		//print_r(self::$DB);
                }
            }
        
        }
        
        public static function initialise($type = "guest"){
            
    		if (!(isset(self::$instance))){
                self::$instance = new Permissions($type);
            }
            return self::$instance;
                
        }

        public static function populateLevelsLookUpTable(){

            self::$levelsLookupTable = array(
                                            // the notes below are guidance only!
                                            
                "god"           => 1000,    // impossible action that no account possesses
                "superuser"     => 400,     // top user account, to administrate administrators
                "admin"         => 300,     // admins usually have create / edit powers for all content
                "contributor"   => 200,     // contributors usually allowed to add / edit their own content
                "member"        => 100,     // members are authenticated users with read-only permission
                "guest"         => 0,        // guests are non-authenticated users who can only access 
                                            // public content
                "nobody"         => -1      // nobody is a default account sed for set-up with less than no permission! 
            );
        }

        public static function getLevel($type){
            if (!$type) return false;
            if (isset(self::$levelsLookupTable[$type])){ 
                return self::$levelsLookupTable[$type];
            }
        }

        public function hasPermission($permRef){
            if (!$permRef) return false;
            return in_array($permRef, $this->userPermissionsTable);
        }
                
        private function populateLookupTables(){
        
            self::populateLevelsLookupTable();
            $this->permissionsLookupTable = array();
            $this->userPermissionsTable = array();
            
            $sql = "SELECT * FROM permissions";
            $query = self::$DB->DB_ExecuteAndProcess($sql);
                        
            if (is_array($query) && sizeof($query) > 0){
                foreach ($query as $data){
                    $permRef = $data["permission_reference"];
                    $permLevel = $data["permission_level"];
                    $this->permissionsLookupTable[$permRef] = self::$levelsLookupTable[$permLevel];
                    if (isset(self::$levelsLookupTable[$this->userType])
                        &&
                        self::$levelsLookupTable[$this->userType] >= self::$levelsLookupTable[$permLevel]
                    ){ 
                        
                        $this->userPermissionsTable[] = $permRef; 
                    }
                }
            }
        }
        
        public function destroy(){
            $this->userPermissionsTable = array();
        }        

        private function checkSanity(){
        
            if (!(class_exists("DB_Mysql"))){ $this->sanity = false; return; }
            self::$DB = DB_Mysql::create(MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDBNAME);
            if (!(in_array("permissions", self::$DB->tables))){ $this->sanity = false; return; }
            $this->sanity = true;

        }
        

    }
?>