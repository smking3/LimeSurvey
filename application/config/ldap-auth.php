 <?php
/**
* See ldap.php, this section works exactly the same
*/
$serverId = 0;
$ldap_server[$serverId]['server']  = "";
$ldap_server[$serverId]['port'] = "";
$ldap_server[$serverId]['protoversion'] = "ldapv3";
$ldap_server[$serverId]['encrypt'] = "start-tls";
$ldap_server[$serverId]['referrals'] = false;
$ldap_server[$serverId]['binddn']   =   "";
$ldap_server[$serverId]['bindpw']   =   "";

/**
* This is different. This is all information for adding users with ldap.
*/
//Must contain info to build full name and email address!
$ldap_query['attrlist'] = array("uid", "sn", "givenName", "mail");

//This must be an array. It will concatenate the given strings in order first to last, and put a space in between each. So, "FirstName LastName"
$ldap_query['fullname'] = array("givenname","sn");
$ldap_query['email'] = "mail";

//default parent user for ldap created users -- might be better to set this elsewhere?
$ldap_query['parent'] = "admin";

$ldap_query['userbase'] = ''; //ou=people,dc=example,dc=com

//This the info ldap will use to attempt a bind, so usually: CN=USER,dc=example,dc=com"
$ldap_query['customDnPrefix'] = ""; //CN=
$ldap_query['customDnSuffix'] = ""; //,dc=example,dc=com

//This is a filter. Again the custom username will be sandwiched between these two strings for the ldap lookup.
$ldap_query['customFilterPrefix'] = "(&(objectCategory=person)(sAMAccountName=";
$ldap_query['customFilterSuffix'] = ")(!(userAccountControl=514)))";


//DONT EDIT BELOW HERE --------------------------------------------------
return array('ldap_server' => $ldap_server, 'ldap_query' => $ldap_query);
?>



