<?php

namespace kosoukhov\ldap;

use yii\base\Component;
use yii\data\ArrayDataProvider;
use kosoukhov\ldap\forms\LoginForm;
use kosoukhov\ldap\services\LdapAuth;
use kosoukhov\ldap\services\LdapSearch;
use kosoukhov\ldap\wrappers\LdapWrapper;


/**
 *
 * Auth and find user in LDAP
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'ldap' => [
 *             'class' => 'kosoukhov\ldap\Connector',
 *             'useCache' => YII_ENV_DEV ? false : true,
 *             'host' => 'ldap.example.com',
 *             'port' => '389',
 *             'baseDN' => 'OU=...,DC=...,DC=...,DC=net',
 *             'userDN' => '@....corp.net',
 *             'groupDN' => '',
 *             //Input your AD login/pass on dev or sys login/pass on test/prod servers
 *             'sysUserLogin' => '',
 *             'sysUserPassword' => '',
 *         ],
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Usage example:
 *
 * ```php
 * if (!Yii::$app->ldap->validateUserCredentials('SAMAccountName', 'password')) {
 *     throw new ErrorException('Incorrect username or password.');
 * }
 * ```
 *
 * ```php
 * echo Yii::$app->ldap->getUserAttributesBySAMAccountName('SAMAccountName', ['mail', 'sn', 'givenname', 'middlename']);
 * ```
 *
 * Usage Widget example:
 * echo kosoukhov\ldap\widgets\UserImage::widget([
 *      'login' => Yii::$app->user->identity->username,
 *      'options' => [
 *          'class' => 'img-circle',
 *          'alt' => 'User Image',
 *      ]
 * ]);
 *
 * @author Kosoukhov V.E.
 * @package kosoukhov\ldap
 * @since 1.0
 */
class Connector extends Component
{

    /**
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * @var int
     */
    public $port = 389;

    /**
     * @var string
     */
    public $baseDN = '';

    /**
     * @var string
     */
    public $userDN = '';

    /**
     * @var string
     */
    public $groupDN = '';


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
     * @var bool Enable / Disable Cache for user image from LDAP
     */
    public $useCache = false;

    /**
     * @var int Cache Duration (in sec.)
     */
    public $cacheDuration = 3600;

    /**
     * @var LdapAuth
     */
    public $ldapAuth;


    /**
     * @var LdapSearch
     */
    public $ldapSearch;


    /**
     * @var array
     */
    private $_attributes;


    /**
     * @var bool
     */
    private $_useMask;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $ldap = new LdapWrapper([
            'host' => $this->host,
            'port' => $this->port,
            'sysUserLogin' => $this->sysUserLogin,
            'sysUserPassword' => $this->sysUserPassword
        ]);

        $this->ldapAuth = new LdapAuth([
            'ldap' => $ldap,
            'userDN' => $this->userDN,
            'baseDN' => $this->baseDN,
            'groupDN' => $this->groupDN
        ]);

        $this->ldapSearch = new LdapSearch([
            'ldap' => $ldap,
            'baseDN' => $this->baseDN,
        ]);
    }

    /**
     * Check username and password in AD
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function validateUserCredentials($username, $password)
    {
        return $this->ldapAuth->authenticate(new LoginForm([
            'username' => $username,
            'password' => $password
        ]));
    }


    /**
     * Search user in AD by Login
     *
     * @param string $login
     * @param array $attributes
     * @param bool $useMask
     * @return ArrayDataProvider
     */
    public function searchUserByLogin($login, $attributes = [], $useMask = true)
    {
        $this->attributes = $attributes;

        if ($useMask) {
            $login = '*' . $login . '*';
        }

        return new ArrayDataProvider([
            'allModels' => $this->ldapSearch->getUserAttributesBySAMAccountName($login, $this->attributes),
        ]);
    }

    /**
     * Search user in AD by Email
     *
     * @param string $email
     * @param array $attributes
     * @param bool $useMask
     * @return array|bool
     */
    public function searchUserByEmail($email, $attributes = [], $useMask = true)
    {
        $this->attributes = $attributes;

        if ($useMask) {
            $email = '*' . $email . '*';
        }

        return $this->ldapSearch->getUserAttributesByEmail($email, $this->attributes);
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (empty($attributes)) {
            $attributes = ['samaccountname', 'mail', 'sn', 'givenname', 'middlename', 'department', 'title', 'jpegphoto'];
        }

        $this->_attributes = $attributes;
    }
}
