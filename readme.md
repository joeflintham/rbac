# RBAC Bolt-on

This RBAC bolt-on is a php-based user authentication system with role-based access control. It is intended as a tool to help while building prototype web-apps which need to authenticate users and provide access rights for various actions.

The RBAC bolt-on is designed to be a quick, hackable way to add role-based user-authentication to an app during prototyping. For production deployment, you'll likely want to beef up the security. For the build process, it can be convenient to test user authentication and permissions using this bolt-on, because it can be added on to your project with just a few lines of code, and then utilised through a number of methods.

## What's this for?

I find when I'm building an app, I'm likely to have very specific requirements about user authentication and permission checking; however, what I really want to do is build the app, not a user authentication system! So I use this bolt-on as a temporary solution while developing and testing my app's functionality with authenticated users. When my development phase is ready, I can then implement a bespoke RBAC for the app.

## Requirements

* PHP 5.3+  
* MySQL 5  

## Usage

Once installed, you can initalise a static object which represents the current user. After including the necessary files, initialise the user as follows:

<pre>
&lt;?php

require_once ("config.php");
require_once ("UserSystem/User.php");

// initialise user

User::setupUser();

?&gt;
</pre>

Then you can authenticate a user in one of four ways: GET, POST, COOKIE or SESSION. Populating any of these data objects with the following parameters:

* user_name - <em>the user name</em>    
* user_password - <em>the user password</em>  

e.g. you can call your php page with a GET query string of the form 

<pre>
/foo/bar/?user_name=yourusername&user_password=yourpassword
</pre>

This will also set the php SESSION variable (if active using session_start()) and set a COOKIE (if enabled) keeping your user logged in until you pass in a GET or POST variable called "logout".
 
In your php script, you can access your user's properties using the static method getUser().

<pre>

User::setupUser();

if (User::getUser()->authenticated){ /* logged in user actions */ }

</pre>

# Permissions

There are several default user types that are set up in the RBAC bolt-on. These are:

<strong>god</strong>: a notional user with impossible powers. included for conceptual purposes; don't use unless you want to create permissions that should never be used. ;-)  
<strong>superuser</strong>: the top level user, who has all permissions  
<strong>admin</strong>: an administrator who can usually perform CRUD actions (Create, Read, Update, Delete)  
<strong>contributor</strong>: useful to assign to user who can create and edit their own items  
<strong>member</strong>: authenticated users with read-only access  
<strong>guest</strong>: unauthenticated users  
<strong>nobody</strong>: a default user with less than no permissions. Used during setup.  

Note that these user_types are all optional and can be interpreted / implemented as you wish.

If you want to use the RBAC system, you will need to populate the permissions database with keys that you wish to test for, and for each key, a permission level corresponding to the user_type who has permission to perform the action. Once you've done this, you can test whether the currently authenticated user has permission to perform such actions with the following code:

<pre>

User::setupUser();

if (User::getUser()->authenticated){ 
   /* logged in user actions */ 

   $permission = 'action_to_perform'; // 'action_to_form' should be a known value in the Permissions table
   if (User::getUser()->hasPermission($permission)){
      /* user has correct access privileges */
   } else {
      // polite rejection message?
   }
} else {
   // redirect to login?
}

</pre>

## Permission cascading

In order to keep the RBAC bolt-on as simple as possible, it is assumed that members of a higher rank inherit all the privileges of lower-ranking user&#95;types. Hence, the superuser has all the permissions of the admin, contributor, member and guest types. A consequence of this is that each user type could be permitted to edit the user&#95;type of lower-ranking users (e.g. a contributor could change a member into a contributor). Of course you can utilise or override this behaviour by planning your permissions table carefully.

## Permission planning

The best way to illustrate permissions is to look at an example from a recent prototype built using Slim PHP (which currently has no mature user-auth or RBAC interface).

Slim uses 'routes' to determine the webapp's functionality; we used the route as the key in the permissions table to maintain ease of use.
 
<pre>

$app->get('/foo/bar/', function () use($app) {
    if (User::getUser()->hasPermission('/foo/bar/')){
        /* user has permission */
    } else {
        // user not permitted: is it because they're not logged in?
        if (!(User::getUser()->authenticated)){
	    /* redirect to login form */
        } else {
	    /* redirect to polite rejection */
        }
    }
});

</pre>

# Installation - short version

First steps (more details follow beneath the initial list):

* publish the RBAC directory contents to a web-accessible location  
* create a MySQL database and user account  
* edit config.php  
* run the setup process  
* add a real user  
* set up the permissions table  

## Installation - walkthrough

You can use phpMyAdmin; easier to use the mysql CLI. This example uses the database name 'rbac', the db username 'rbac&#95;user' and the password 'rbac&#95;pass'. Substitute with your own.

<pre>

$ mysql -u db_admin_username -p
<enter password at prompt>
mysql> create database rbac;
mysql> grant all on rbac.* to rbac_user@localhost identified by 'rbac_pass';

</pre>

Edit config.php to reflect the database / credentials you used above. If you don't have CLI access to mysql, consider changing your host.

Now create a temporary php script to intialise the database (there is one included in the package, 'index.php', which should be removed after use). Assuming your php script is called 'setup.php', point your browser at:

<pre>

http://your.domain.com/path/to/rbac/index.php?user_name=nobody&user_password=nobody

</pre>

On first run, this will create the necessary tables for your users and permissions.

You now have a user table with a nobody user; and an empty permissions table.

# Creating users

If you want to get up an running with some test users quickly, you can add them at the mysql CLI. The table only stores sha1 password hashes, so your insert comand should look something like this:

<pre>
mysql> insert into users (user_name, user_password, user_displayName, user&#95;type) values ('joe', '16a9a54ddf4259952e3c118c763138e83693d7fd', 'Joe', 'member');
</pre>

The above example inserts the user joe with password 'joe'. To create a sha1 password hash, use the php interactive shell. Here we generate a sha hash for 'password' - substitute your own:

<pre>
$ php -a
echo sha1('password');
</pre>

You can then copy and paste the resultant hash into your mysql command. 

The RBAC User object also has methods for creating, editing and deleting users.

<pre>

User::createUser(array(
    "user_name"         => "",
    "user_password"     => "",
    "user_displayName"  => "",
    "user&#95;type"         => ""
));

User::editUser(array(
    "user_name"         => "",
    "user_displayName"  => "",
    "user&#95;type"         => ""
));

User::changePassword(array(
    "user_name"         => "",
    "user_new_password"  => "",
    "user_new_password_repeat"  => ""
));

User::deleteUser(array(
    "user_name"         => ""
));

</pre>

All the above commands return Boolean:True on success, and an ErrorHandler object on failure. The ErrorHandler object has a public property listing one or more error messages:

<pre>

$response = User::editUser(array(
    "user_name" => "",
    "user_displayName" => "",
    "user&#95;type" => ""
)); // fails for all three values, returns ErrorHandler object

if (is_object($response)){
   print_r($response->messageList); // spits out array of error messages
} else if ($response) {
   // success
}

</pre>

## Set up permissions

Using the mysql cli or phpMyAdmin, add the permissions and roles associated with them to the Permissions table. e.g.

<pre>
mysql> insert into permissions (permission_description, permission_reference, permission_level) values ('Example permission','demo','superuser');
</pre>

The above record allows you to test whether the current user has permission to complete and action with the following code:

<pre>

    if (User::getUser()->hasPermission('demo')){ // user must be a superuser
        /* user is a superuser, has permission */
    } else {
        // user not permitted
    }

</pre>

# Notes

* Remove index.php after use  
* Don't use in production (savvy users can log in by hacking cookies and sessions, albeit only if they already have credentials, and you probably want to direct them through specific routes for analytics, conversion, etc)
* If you're already using a DB class you should find it easy to wrap the User and Permissions classes around that instead of the DB class used here.