<?php

    ini_set("display_errors", true);
    require_once ("config.php");
    require_once ("UserSystem/User.php");
    $user = User::setupUser();
    print_r($user);

?>