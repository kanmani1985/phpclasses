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
        $url = $this->cpanel_path . $url;
        if (is_array($data)) {
            $url = $url . '?';
            foreach ($data as $key => $value) {
                $url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $url = substr($url, 0, -1);
        }
        $response = '';
        $fp = fsockopen($this->cpanel_ssl . $this->cpanel_host, $this->cpanel_port);
        if (!$fp) {
            return false;
        }
        $out = 'GET ' . $url . ' HTTP/1.0' . "\r\n";
        $out .= 'Authorization: Basic ' . $this->cpanel_auth . "\r\n";
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
        $data['oldpass'] = $this->cpanel_password;
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

    /*
     * @description: Get hosting package name
     * @param : no parameter passed
     * @return string
     * @access public.
     */
    function getHostingPackage() {
        return $this->parseIndex('Hosting package');
    }

    /*
     * @description: Get shared IP address
     * @param : no parameter passed
     * @return string
     * @access public.
     */
    function getSharedIP() {
        return $this->parseIndex('Shared Ip Address');
    }
    /* @description:Create email account in cPanel
     * @param string $email email account 
     * @param string $password email account password
     * @param int $quota quota for email account in megabytes
     * @return bool Returns true on success or false on failure.
     * @access public.
     */

    function createEmailAccount($email,$password,$quota='5') {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $data['password'] = $password;
        $data['quota'] = $quota;
        $response = $this->fetchData('mail/doaddpop.html', $data);
        if (strpos($response, 'failure') || strpos($response, 'already exists')) {
            return false;
        }
        return true;
    }
    
    /*
     * @description:Delete email account, Permanenetly removes email account.
     * @param string $email email account for delete
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function deleteEmailAccount($email) {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $response = $this->fetchData('mail/realdelpop.html', $data);
        if (strpos($response, 'success')) {
            return true;
        }
        return false;
    }
    
    /*
     * @description:Get space used by account
     * @param string $email email account
     * @return int Returns the amount of disk space used by email account in megabytes.
     * @access public.
     */
    function getUsedSpaceOfEmailAccount($email) {
        $usedSpace = array();
        preg_match('/' . $email . '@' . $this->cpanel_host . "<\\/font><\\/td>\n        <td align=\"center\" valign=\"top\">([^&]*)/", $this->fetchData('mail/pops.html?extras=disk'), $usedSpace);
        return $usedSpace[1];
    }

    /*
     * @description:Get account storage quota
     * @param string $email email account
     * @return int Returns amount of disk space allowed for email account in megabytes.
     * @access public.
     */
    function getQuotaOfEmailAccount($email) {
        $quota = array();
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        preg_match('/quota" value="([^"]*)/', $this->fetchData('mail/editquota.html', $data), $quota);
        return ($quota[1] == 0) ? 'Unlimited' : intval($quota[1]);
    }

    /*
     * @description:Modify account storage quota
     * @param int $quota quota for email account in megabytes
     * @param string $email email account
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function changeQuotaOfEmailAccount($quota,$email) {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $data['quota'] = $quota;
        $response = $this->fetchData('mail/doeditquota.html', $data);
        if (strpos($response, 'success')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Change email account password
     * @param string $password email account password
     * @param string $email email account
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function changePasswordOfEmailAccount($password,$email) {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $data['password'] = $password;
        $response = $this->fetchData('mail/dopasswdpop.html', $data);
        if (strpos($response, 'success') && !strpos($response, 'failure')) {
            return true;
        }
        return false;
    }

    /*
     * @description:List email forwarders
     * @param string $email email account
     * @return array Returns a numerically-indexed array of forwarders for the email account. Returns an empty array if there are no forwarders.
     * @access public.
     */
    function listForwardersOfEmailAccount($email) {
        $forwarders = array();
        preg_match_all('/\?email=' . $email . '@' . $this->cpanel_host . '=([^"]*)/', $this->fetchData('mail/fwds.html'), $forwarders);
        return $forwarders[1];
    }

    /*
     * @description:Create email forwarder
     * @param string $email email account
     * @param string $forward forwarding address
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function addForwarderOfEmailAccount($forward,$email) {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $data['forward'] = $forward;
        $response = $this->fetchData('mail/doaddfwd.html', $data);
        if (strpos($response, 'redirected')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Delete email forwarder ,Permanently removes the account's email forwarder and returns true.
     * @param string $forwarder forwarding address to delete
     * @param string $email email account
     * @return bool
     * @access public.
     */
    function delForwarderOfEmailAccount($forwarder,$email) {
        $data['email'] = $email . '@' . $this->cpanel_host . '=' . $forwarder;
        $this->fetchData('mail/dodelfwd.html', $data);
        return true;
    }

    /*
     * @description:Create email autoresponder
     * @param string $from from email address
     * @param string $subject email subject line
     * @param string $charset character set
     * @param bool $html true for HTML email
     * @param string $body body of email message
     * @param string $email email account
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function addAutoResponder($from, $subject, $charset, $html, $body,$email) {
        $data['email'] = $email;
        $data['domain'] = $this->cpanel_host;
        $data['from'] = $from;
        $data['subject'] = $subject;
        $data['charset'] = $charset;
        if ($html) {
            $data['html'] = $html;
        }
        $data['body'] = $body;
        $response = $this->fetchData('mail/doaddars.html', $data);
        if (strpos($response, 'success') && !strpos($response, 'failure')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Delete email autoresponder ,
     * @param string $email email account
     * @return bool Deletes autoresponder for email account if it exists , and returns true.
     * @access public.
     */
    function delAutoResponder($email) {
        $this->fetchData('mail/dodelautores.html?email=' . $email . '@' . $this->cpanel_host);
        return true;
    }
    
    /*
     * @description:Get default address
     * @param string $domain domain name
     * @return string Retrieves the default email address for the domain.
     * @access public.
     */
    function getDefaultAddressOfDomain($domain) {
        $default = explode('<b>' . $domain . '</b>', $this->fetchData('mail/def.html'));
        if ($default[1]) {
            $default = explode('<td>', $default[1]);
            $default = explode('</td>', $default[1]);
            return trim($default[0]);
        }
    }
    
    /*
     * @description: Modify default address Changes the default email address for the domain. 
     * @param string $adderss new default address
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function changeDefaultAddressOfDomain($address) {
        $data['domain'] = $this->cpanel_host;
        $data['forward'] = $address;
        $response = $this->fetchData('mail/dosetdef.html', $data);
        if (strpos($response, 'is now')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Park domain
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function parkDomain() {
        $data['domain'] = $this->cpanel_host;
        $response = $this->fetchData('park/doaddparked.html', $data);
        if (strpos($response, 'success') && !strpos($response, 'error')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Delete parked domain
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function unparkDomain() {
        $data['domain'] = $this->cpanel_host;
        $response = $this->fetchData('park/dodelparked.html', $data);
        if (strpos($response, 'success') && !strpos($response, 'Error')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Create addon domain
     * @param string $user username or directory
     * @param string $pass password
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function addonDomain($user, $pass) {
        $data['domain'] = $this->cpanel_host;
        $data['user'] = $user;
        $data['pass'] = $pass;
        $response = $this->fetchData('addon/doadddomain.html', $data);
        if (strpos($response, 'added') && !strpos($response, 'Error')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Delete addon domain
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function delAddonDomain() {
        $data['domain'] = $this->cpanel_host;
        $response = $this->fetchData('addon/dodeldomain.html', $data);
        if (strpos($response, 'success') && !strpos($response, 'Error')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Create subdomain
     * @param string $subdomain name of subdomain to create
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function createSubdomain($subdomain) {
        $data['domain'] = $subdomain;
        $data['rootdomain'] = $this->cpanel_host;
        $response = $this->fetchData('subdomain/doadddomain.html', $data);
        if (strpos($response, 'added') && !strpos($response, 'Error')) {
            return true;
        }
        return false;
    }
    /*
     * @description:Delete subdomain
     * @param string $subdomain name of subdomain to delete
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function delSubdomain($subdomain) {
        $data['domain'] = $subdomain . '_' . $this->cpanel_host;
        $response = $this->fetchData('subdomain/dodeldomain.html', $data);
        if (strpos($response, 'Removed')) {
            return true;
        }
        return false;
    }
    /*
     * @description:Get subdomain redirection
     * @return string Returns the URL a subdomain is redirected to.
     * @access public.
     */
    function getSubdomainRedirect($subdomain) {
        $redirect = array();
        $data['domain'] = $subdomain . '_' . $this->cpanel_host;
        preg_match('/40 value="([^"]*)/', $this->fetchData('subdomain/doredirectdomain.html', $data), $redirect);
        return $redirect[1];
    }

    /*
     * @description:Redirect subdomain ,Redirects a subdomain of the current domain to another address.
     * @param string $subdomain name of subdomain
     * @param string $url url to redirect to
     * @return bool Returns true if sucsess false if fails.
     * @access public.
     */
    function redirectSubdomain($subdomain, $url) {
        $data['domain'] = $subdomain . '_' . $this->cpanel_host;
        $data['url'] = $url;
        $response = $this->fetchData('subdomain/saveredirect.html', $data);
        if (strpos($response, 'redirected') && !strpos($response, 'Disabled')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Remove subdomain redirection
     * @param string $subdomain name of subdomain
     * @return bool  Returns true if sucsess false if fails.
     * @access public.
     */
    function delRedirectSubdomain($subdomain) {
        $data['domain'] = $subdomain . '_' . $this->cpanel_host;
        $response = $this->fetchData('subdomain/donoredirect.html', $data);
        if (strpos($response, 'disabled')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Create FTP account
     * @param string $account username of ftp account
     * @param string $password account password
     * @param string $quota disk space quota in megabytes
     * @param string directory user's home directory
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function createFtp($account,$password, $quota, $directory) {
        $data['login'] = $account;
        $data['password'] = $password;
        $data['quota'] = $quota;
        $data['homedir'] = $directory;
        $response = $this->fetchData('ftp/doaddftp.html', $data);
        if (strpos($response, 'failure') || strpos($response, 'Fatal') || !strpos($response, 'Added')) {
            return false;
        }
        return true;
    }

    /*
     * @description:Get used space
     * @param string $account username of ftp account
     * @return int Returns the amount of disk space used by the FTP account.
     * @access public.
     */
    function getUsedSpaceOfFtp($account) {
        $usedSpace = explode('<td>' . $account . '</td>', $this->fetchData('ftp/accounts.html'));
        $usedSpace = explode('</td><td>', $usedSpace[1], 2);
        return floatval(substr($usedSpace[1], 0, strpos($usedSpace[1], '/')));
    }

    /*
     * @description:Get storage quota
     * @param string $account username of ftp account
     * @return bool Returns the storage quota of the FTP account in megabytes.
     * @access public.
     */
    function getQuotaOfFtp($account) {
        $quota = array();
        $data['acct'] = $account;
        preg_match('/"quota" value="([^"]*)/', $this->fetchData('ftp/editquota.html', $data), $quota);
        return ($quota[1] == 0) ? 'Unlimited' : intval($quota[1]);
    }

    /*
     * @description:Change storage quota Modifies the maximum disk space allowed for the FTP account.
     * @param string $account username of ftp account
     * @param int $quota new quota in megabytes
     * @return bool Returns true on success or false on failure.
     * @access public.
     */
    function changeQuotaOfFtp($account,$quota) {
        $data['acct'] = $account;
        $data['quota'] = $quota;
        $response = $this->fetchData('ftp/doeditquota.html', $data);
        if (strpos($response, 'success')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Change password ,Changes the FTP account password
     * @param string $account username of ftp account
     * @param string $password new password
     * @return bool returns true on success or false on failure.
     * @access public.
     */
    function changePasswordOfFtp($account,$password) {
        $data['acct'] = $account;
        $data['password'] = $password;
        $response = $this->fetchData('ftp/dopasswdftp.html', $data);
        if (strpos($response, 'Changed')) {
            return true;
        }
        return false;
    }

    /*
     * @description:Delete FTP account ,Permanently removes the FTP account and 
     * @param string $account username of ftp account
     * @return bool Returns true on success or false on failure.
     * @access public. 
     */
    function deleteFtp($account) {
        $data['login'] = $account;
        $response = $this->fetchData('ftp/realdodelftp.html', $data);
        if (strpos($response, 'deleted')) {
            return true;
        }
        return false;
    }
    
    
}

?>