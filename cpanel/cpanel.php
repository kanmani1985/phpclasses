<?php

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
    
    /*@description: Function used to Change Cpanel Password.
     *@param string $password -> New password. 
     *@return bool Returns true on success or false on failure. 
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
     * 
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
     * @description:List all domain in whm panel
     * @param : no parameter passed
     * @return array : Returns a numerically-indexed array on success or false on failure.
     * 
     */
    function listDomains() {
        $domainList = array();
        preg_match_all('/<option value="([^"]*)/', $this->fetchData('mail/addpop2.html'), $domainList);
        if (count($domainList[1]) > 0) {
            return $domainList[1];
        }
        return false;
    }

}

?>