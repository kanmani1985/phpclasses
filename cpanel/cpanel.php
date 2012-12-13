<?php
/*
 * Cpanel API class
 *
 * @author habid
 * @package phpclasses
 * @copyright
 * @license
 * @version 1.0
 * @created date 
 */
class cpanel {

    protected $cpanel_host;
    protected $cpanel_username;
    protected $cpanel_password;
    protected $cpanel_theme;
    protected $cpanel_auth;
    protected $cpanel_port;
    protected $cpanel_path;
    protected $cpanel_ssl;

    function __construct($host, $username, $password, $port = 2082, $ssl = false, $theme = 'x3') {
        $this->cpanel_ssl = $ssl ? 'ssl://' : '';
        $this->cpanel_username = $username;
        $this->cpanel_password = $password;
        $this->cpanel_theme = $theme;
        $this->cpanel_auth = base64_encode($username . ':' . $password);
        $this->cpanel_port = $port;
        $this->cpanel_host = $host;
        $this->cpanel_path = '/frontend/' . $theme . '/';
    }

    function fetchData($url, $data = '') {
        $url = $this->path . $url;
        if (is_array($data)) {
            $url = $url . '?';
            foreach ($data as $key => $value) {
                $url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $url = substr($url, 0, -1);
        }
        $response = '';
        $fp = fsockopen($this->ssl . $this->host, $this->port);
        if (!$fp) {
            return false;
        }
        $out = 'GET ' . $url . ' HTTP/1.0' . "\r\n";
        $out .= 'Authorization: Basic ' . $this->auth . "\r\n";
        $out .= 'Connection: Close' . "\r\n\r\n";
        fwrite($fp, $out);
        while (!feof($fp)) {
            $response .= @fgets($fp);
        }
        fclose($fp);
        return $response;
    }
    
    function parseIndex($key, $type = 'string') {
        $value = array();
        preg_match('/' . $key . '<\/td>' . "\n" . '               <td class="index2">(.*)<\/td>/', $this->fetchData('index.html'), $value);
        settype($value[1], $type);
        return $value[1];
    }

    /* @description: Function used to Change Cpanel Password.
     * @param string $password -> New password. 
     * @return bool Returns true on success or false on failure. 
     * @access public
     */

    function changePassword($password) {
        $data['oldpass'] = $this->password;
        $data['newpass'] = $password;
        $response = $this->fetchData('passwd/changepass.html', $data);
        if (strpos($response, 'has been') && !strpos($response, 'could not')) {
            return true;
        }
        return false;
    }

    /*
     * @description : Function to Fetch Contact email provided in the cpanel account
     * @param : no parameter passed
     * @return string :Returns the contact email address provided in cPanel.
     * @access public
     */

    function fetchContactEmail() {
        $email = array();
        preg_match('/email" value="(.*)"/', $this->fetchData('contact/index.html'), $email);
        return $email[1];
    }

    /*
     * @description : Function to change contact email in cpanel
     * @param string new contact email address
     * @return bool: Returns true on success or false on failure. 
     * @access public
     */

    function changeContactEmail($email) {
        $data['email'] = $email;
        $response = $this->fetchData('contact/saveemail.html', $data);
        if (strpos($response, 'has been')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Fetch all domain in whm panel
     * @param : no parameter passed
     * @return array : Returns a numerically-indexed array on success or false on failure.
     * @access public
     */

    function fetchAllDomains() {
        $domainList = array();
        preg_match_all('/<option value="([^"]*)/', $this->fetchData('mail/addpop2.html'), $domainList);
        if (count($domainList[1]) > 0) {
            return $domainList[1];
        }
        return false;
    }

    /* @description:Fetch all email account in the cpanel for the host
     * @param : no parameter passed
     * @return array : Returns a numerically-indexed array on success or false on failure.
     * @access public
     */

    function fetchAllMailAccounts() {
        $accountList = array();
        preg_match_all('/\?acct=([^"]*)/', $this->fetchData('mail/pops.html'), $accountList);
        if (count($accountList[1]) > 0) {
            return $accountList[1];
        }
        return false;
    }

    /*
     * @description:Fetch all Database user in the host
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array on success. Returns an empty array if no users exist.
     * @access public
     */
    function fetchAllDBUsers() {
        $accountList = array();
        preg_match_all('/\?user=([^"]*)/', $this->fetchData('sql/index.html'), $accountList);
        return $accountList[1];
    }

    
    /*
     * @description:Fetch all MYSQL Database
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array on success. Returns an empty array if no databases exist.
     * @access public
     */
    function fetchAllDatabases() {
        $databaseList = array();
        preg_match_all('/deldb.html\?db=([^"]*)/', $this->fetchData('sql/index.html'), $databaseList);
        return $databaseList[1];
    }

    /*
     * @description:Fetch all FTP accounts
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array on success or false on failure. 
     * @access public
     */
    function fetchAllFTPAccounts() {
        $accountList = Array();
        preg_match_all('/passwdftp.html\?acct=([^"]*)/', $this->fetchData('ftp/accounts.html'), $accountList);
        return array_unique($accountList[1]);
    }

    /*
     * @description:Fetch all parked domains
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array on success. Returns an empty array if no domains are parked.
     * @access public
     */
    function fetchAllParkedDomain() {
        $domainList = array();
        preg_match_all('/<option value="([^"]*)/', $this->fetchData('park/index.html'), $domainList);
        return $domainList[1];
    }

    /*
     * @description:Fetch all addon domains
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array of comma-delimited values on success. Returns an empty array if no addon domains exist.
     * @access public
     */
    function fetchAllAddonsDomain() {
        $domainList = array();
        $data = explode('Remove Addon', $this->fetchData('addon/index.html'));
        preg_match_all('/<option value="(.*)">(.*)<\/option>/', $data[1], $domainList);
        return $domainList[0];
    }

    /*
     * @description:Fetch all subdomains
     * @param : no parameter passed
     * @return array Returns a numerically-indexed array on success.  Returns an empty array if no subdomains exist.
     * @access public
     */
    function fetchAllSubdomains() {
        $domainList = array();
        $domains = explode('</select>', $this->fetchData('subdomain/index.html'));
        $domains = explode('</select>', $domains[2]);
        preg_match_all('/<option value="(.*)">(.*)<\/option>/', $domains[0], $domainList);
        return $domainList[2];
    }
    
    /**
     * @description:Fetch all Apache redirects
     * @param : no parameter passed
     * @return array These may be permanent or temporary redirects (status codes 301 and 302). Returns a numerically-indexed array on success. Returns an empty array if no redirects exist.
     * @access public
     */
    function fetchAllRedirects() {
        $redirectList = array();
        preg_match_all('/<option value="\/([^"]*)/', $this->fetchData('mime/redirect.html'), $redirectList);
        return $redirectList[1];
    }
    
    
    /*
     * @description:Get free disk space
     * @param : no parameter passed
     * @return mixed Returns the amount of disk space available in megabytes.
     * @access public
     */
    function getFreeSpace() {
        $freeSpace = $this->parseIndex('Disk space available', 'float');
        return ($freeSpace == 0) ? 'Unlimited' : floatval($freeSpace);
    }

    /*
     * @description:Get used disk space
     * @param : no parameter passed
     * @return float Returns the amount of disk space used in megabytes.
     * @access public.
     */
    function getSpaceUsed() {
        return $this->parseIndex('Disk Space Usage', 'float');
    }

    /*
     * @description: Get MySQL space usage
     * @param : no parameter passed
     * @return float Returns the amount of disk space used by MySQL databases in megabytes.
     * @access public.
     */
    function getMySQLSpaceUsed() {
        return $this->parseIndex('MySQL Disk Space', 'float');
    }

    /*
     * @description:Get bandwidth usage
     * @param : no parameter passed
     * @return float Returns the amount of bandwidth used this month in megabytes.
     * @access public.
     */
    function getBandwidthUsed() {
        return $this->parseIndex('Bandwidth \(this month\)', 'float');
    }

    /**
     * @description: Get hosting package name
     * @param : no parameter passed
     * @return string
     * @access public.
     */
    function getHostingPackage() {
        return $this->parseIndex('Hosting package');
    }

    /**
     * @description: Get shared IP address
     * @param : no parameter passed
     * @return string
     * @access public.
     */
    function getSharedIP() {
        return $this->parseIndex('Shared Ip Address');
    }

}

?>