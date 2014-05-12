<html>
    <head>
    </head>
    <body>
        <h1>Prototype to add lconf host</h1>
<?php

/**
 * This is the only file needed by lconfadd.
 *
 * it holds all the code
 */

error_reporting(E_ALL | E_STRICT);

/**
 * add hosts to LConf without using LConf
 *
 * This php class shows a simple webinterface where a user can fill out a form with the minimal properties used to create a new host.
 * So even users not trained to use LConf can create Hosts by themselves.
 * An optional comment section is provided where users can leave a message to the monitoring admins if further work is needed with a host.
 * You need an external file with ldap connection parameters
 *
 * @todo implement a way to inform the monitoring admins that some extra work is to be done
 * @todo provide a webinterface to the logfile
 * @todo allow monitoring admins to check off items of the logfile when extra work is finished
 * @todo move php code into its own file without html so it can be more easily integrated into other webinterfaces e.g. icinga-web.
 * @todo move path for include of ldap connection parameters to variable?
 * @todo write documentation about ldap connection parameters
 */

class lconfadd
{

    /**
     * this variable is used to show if all needed data ist available.
     * 
     * set to false if something is missing and adding will fail.
     *
     * @var boolean when this is True the host will be created
     */

    private $datacomplete = True;

    /** 
     * filename and path of the logfile to use.
     *
     * this should be replaced with a database connection in upcoming versions.
     * keeping a private logfile might be an option but not for this use. Now this file does not keep information
     * wether the scripts had errors but logs the hosts that were created.
     * Intended use is to inform admins of newly added hosts so they can check off any host that needs
     * extra care and has been dealt with.
     *
     * @var string  The path of the logfile to use. May be replaced with database connection
     * @todo replace with database connection
     */ 

    private $createlogfile = '/tmp/lconfadd.log';

    /**
     * this variable holds the ldapconnection
     * 
     * @todo maybe use this variable as param / return value for functions
     * @todo initializing this variable with an empty string seems wrong. check if there are better options
     */

    private $ldapconnection = "";

    /**
     * the fqdn of the host to be created.
     *
     * this is empty but will be filled later
     *
     * @var string holds fqdn of host
     * @todo check if value is valid fqdn
     * @todo make variable param and return value instead of global inside class
     */

    private $fqdn = "";

    /**
     * this functions cleans every input from potentially harmful code
     *
     * taken from http://www.w3schools.com/php/php_form_validation.asp
     *
     * @param string $data this takes a string from an input from a html form.
     *
     * @return string the returned string is stripped from potentially harmful characters
     */

    private function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     * function cleanme
     *
     * this function is used for cleanup. mostly closing the ldap connection
     *
     * @todo do some error checking and logging while cleaning up
     * @todo make the ldap connection to clean up itself a parameter
     * @todo implement return value for cleanme function to show errors
     */

    private function cleanme() {
        // never forget to close the connection
        ldap_close($this->ldapconnection);
    }


    /**
     * lconfadd constructor function
     *
     * @todo split constructor into more functions
     * @todo move ldap connect and ldap bind in standalone functions
     * @todo move display of values (when called with _POST arguments) in standalone function maybe combined with creation of host
     * @todo move logging into standlone function
     * @todo move creation of form into standalone function
     */

    public function __construct() {

        include 'ldapcon.php';

        // other used variables
        // these will be set later

        $ip = $alias = $selectstructobj = "";
        $fqdnerr = $iperr = $aliaserr ="";

        /**
         * ldap connection
         */

        $this->ldapconnection=ldap_connect("localhost") or die ("Can not connect to LDAP");

        if ($this->ldapconnection) {

            // ldap server expects protocol v3 which has to be set explicitly

            ldap_set_option($this->ldapconnection, LDAP_OPT_PROTOCOL_VERSION, 3);

            $ldapbind=ldap_bind($this->ldapconnection, $binddn, $bindpw) or die ("Can not bind to LDAP");

            // -------------------------
            // what to do when called by post
            // -------------------------

            if (isset($_POST['createhost'])) {

                // send all input data through test_input function to strip harmful characters
                // data from select input does not need to be stripped

                if (empty($_POST['fqdn'])) {
                    $fqdnerr = "FQDN must be not be empty";
                    $this->datacomplete = False;
                } else {
                    $this->fqdn = $this->test_input($_POST['fqdn']);
                }
                if (empty($_POST['ip'])) {
                    $iperr ="IP address must not be empty";
                    $this->datacomplete = False;
                } else {
                    $ip = $this->test_input($_POST['ip']);
                }
                $alias = $this->test_input($_POST['alias']);

                // the select can not be used to inject code but the script might be called from another script

                $selectstructobj = $this->test_input($_POST['selectstructobj']);

                $comment = $this->test_input($_POST['comment']);

                // output information of host to be created

                echo '<h2>Your newly created host</h2>';

                echo 'FQDN of the host: ' . $this->fqdn;
                echo '<br />';
                echo 'IP address: ' . $ip;
                echo '<br />';
                echo 'Description: ' . $alias;
                echo '<br />';
                echo 'StructObj to put the new host into: ' . $selectstructobj;
                echo '<br />';
                echo 'Comment: ' . $comment;
                echo '<br />';
                if ($this->datacomplete == False) {
                    echo '<em>Data missing! Your host has not been created</em><br />';
                }

                // prepare host array. object class sets what properties have to be used

                $newhost["objectclass"]='lconfHost';
                $newhost["cn"]=$this->fqdn;
                $newhost["lconfAddress"]=$ip;
                $newhost["lconfAlias"]=$alias;

                // build the dn for the new host object from fqdn and ou

                $dnnewhost='cn='.$this->fqdn.','.$selectstructobj;

                // add new host object

                if ($this->datacomplete) {
                    $retval=ldap_add($this->ldapconnection,$dnnewhost,$newhost);
                    echo '<br /><em>';
                    if ($retval) {
                        echo 'Your host was succesfully created.';

                        // log the newly created host into logfile
                        // further relaeses might use database for this

                        $log = fopen($this->createlogfile,"a");
                        $logentry = "$this->fqdn;$ip;$alias;$comment;$dnnewhost\n";
                        fwrite($log,$logentry);
                        fclose($log);
                    } else {
                        echo 'There was an error creating your host.';
                    }
                echo '</em><br />';
                }
            }

            // -------------------------
            // end of: what to do when called by post
            // -------------------------
            //
            // the following is done every time the script is called

            // do an ldap search for lconfStructuralObject

            $allou=ldap_search($this->ldapconnection,$basedn,"objectClass=lconfStructuralObject");

            // ldap_count_entries can be used for counting only

            // echo "structObj found " . ldap_count_entries($this->ldapconnection,$allou) . "<br />";

            // to use the content of the OUs you have to use ldap_get_entries with an object (?)

            $structobj = ldap_get_entries($this->ldapconnection,$allou);

            // makes ldap_count_entries obsolete. is used for demo purposes only

            // echo "Found information about " . $structobj["count"] . " structObj.<br />";


            echo '<h2>Create new host</h2>';

            // action value is derived from PHP_SELF variable containing script name which is stripped from potentially harmful html characters

            echo '<form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) .'">';

            echo '<table><tr>';
            echo '<td>Structural Object to put the new host into</td>';

            // the following loop goes through all items of the structobj array
            // str_replace is used twice nested to remove the basedn from the value shown and remove all "ou="
            // a html select item is used, where the dn of the structObj is the value and the dn, cleaned with beforementioned str_replace, is used as content

            echo '<td><select name="selectstructobj">';
            for ($i=0; $i<$structobj["count"]; $i++) {
                // echo "dn is " . $structobj[$i]["dn"] . "<br />";
                echo '<option value="'.$structobj[$i]["dn"].'">'.str_replace("ou=","",str_replace($basedn,"",$structobj[$i]["dn"])).'</option>';
            } 
            echo '</select></td>';
            echo '</tr><tr>';

            echo '<td>Hostname (FQDN):</td>';
            echo '<td><input type="text" name="fqdn" /> ' . $fqdnerr . '</td>';
            echo '</tr><tr>';

            echo '<td>IP Adress:</td>';
            echo '<td><input type="text" name="ip" /> ' . $iperr . '</td>';
            echo '</tr><tr>';

            echo '<td>Host Description:</td>';
            echo '<td><input type="text" name="alias" /> ' . $aliaserr . '</td>';
            echo '</tr><tr>';

            echo '<td>Comment:</td>';
            echo '<td><textarea name="comment" cols="35" rows="5">For special needs of this host.</textarea></td>';

            echo '</tr></table>';

            echo '<input type="submit" name="createhost" value="Create Host" />';

            echo '</form>';

            $retval=$this->cleanme();
        }
    }
}



$la = new lconfadd();


?>
</body>
</html>

