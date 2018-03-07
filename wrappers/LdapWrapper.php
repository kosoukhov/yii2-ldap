<?php

namespace kosoukhov\ldap\wrappers;

define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);

use yii\base\BaseObject;
use kosoukhov\ldap\exceptions\LdapException;
use kosoukhov\ldap\forms\LoginForm;

/**
 * Wrapper for LDAP
 *
 * @author VKosoukhov
 */
class LdapWrapper extends BaseObject
{

    /**
     * Optional account with higher privileges for searching
     * This should be set to a domain admin account
     * @var string
     */
    public $sysUserLogin = '';

    /**
     * Optional account with higher privileges for searching
     * This should be set to a domain admin account
     * @var string
     */
    public $sysUserPassword = '';

    /**
     * AD host
     * @var string
     */
    public $host = '';

    /**
     * AD port
     * @var string
     */
    public $port = '';

    /**
     * @var resource
     */
    private $_ldapConnection;

    /**
     * @var resource
     */
    private $_bind;

    /**
     * @throws LdapException
     */
    public function init()
    {
        parent::init();

        SocketWrapper::ping($this->host, $this->port);

        if ($this->ldapSupported() === false) {
            throw new LdapException('No LDAP support for PHP.  See: http://www.php.net/ldap');
        }

        try {
            $this->ldapConnection = ldap_connect($this->host, $this->port);
        } catch (Exception $e) {
            throw new LdapException('"' . get_class($this) . '::__construct LDAP: Cannot run ldap_connect: ' . $e->getMessage());
        }

        $this->setOptions();

        return true;
    }

    /**
     * Detect LDAP support in php
     *
     * @return bool
     */
    protected function ldapSupported()
    {
        if (!function_exists('ldap_connect')) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    protected function setOptions()
    {
        ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    /**
     * @return resource
     */
    public function getLdapConnection()
    {
        return $this->_ldapConnection;
    }

    /**
     * Bind to LDAP directory
     *
     * @param resource $_ldapConnection
     */
    public function setLdapConnection($ldapConnection)
    {
        $this->_ldapConnection = $ldapConnection;
    }

    /**
     * Check correct username and password
     *
     * @param LoginForm $LoginForm
     * @return bool
     */
    public function checkAuth(LoginForm $LoginForm)
    {
        $this->bind = $LoginForm;

        return $this->bind;
    }

    /**
     * Search user in AD
     * @param string $dn
     * @param string $filter
     * @param array $attributes
     * @return bool|resource
     * @throws LdapException
     */
    public function search($dn, $filter, $attributes)
    {
        $extended_error = "";
        $error = "";
        $res = false;

        try {
            if (!$this->bind) {
                $this->bind = new LoginForm([
                    'username' => $this->sysUserLogin,
                    'password' => $this->sysUserPassword,
                ]);
            }

            $res = ldap_search($this->ldapConnection, $dn, $filter, $attributes);

        } catch (\Exception $e) {
            if (ldap_get_option($this->ldapConnection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
                $error = "Error Search in LDAP: $extended_error";
            } else {
                $error = 'LDAP:  ldap_search error: ' . $e->getMessage() . '. Ldap says: ' . ldap_err2str(ldap_errno($this->ldapConnection));

                throw new LdapException($error);
            }
        }

        return $res;
    }

    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Bind to LDAP directory
     *
     * @param LoginForm $LoginForm
     * @return bool
     */
    public function setBind(LoginForm $LoginForm)
    {
        $extended_error = "";
        $error = "";
        $this->_bind = false;

        try {
            $this->_bind = ldap_bind($this->ldapConnection, $LoginForm->username, $LoginForm->password);

        } catch (\Exception $e) {
            if (ldap_get_option($this->ldapConnection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
                $error = $extended_error . ' with username ' . $LoginForm->username . ' and password ' . $LoginForm->password;
            } else {
                $error = 'LDAP: Cannot authenticate with username ' . $LoginForm->username . ' and password ' . $LoginForm->password . '. Error: ' . $e->getMessage() . '. Ldap says: ' . ldap_err2str(ldap_errno($this->ldapConnection));
            }

            if (!strpos($error, 'data 52e')) {
                //Returns when username is valid but password/credential is invalid.
                //http://ldapwiki.com/wiki/Common%20Active%20Directory%20Bind%20Errors
                throw new LdapException($error);
            }
        }

        return $this->_bind;
    }

    /**
     * Default Destructor
     * Closes the LDAP connection
     *
     * @return void
     */
    function __destruct()
    {
        $this->close();
    }

    /**
     * Closes the LDAP connection
     *
     * @return void
     */
    public function close()
    {
        if ($this->ldapConnection) {
            @ldap_close($this->ldapConnection);
        }
    }
}
