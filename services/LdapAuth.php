<?php

namespace kosoukhov\ldap\services;

use yii\base\BaseObject;
use kosoukhov\ldap\forms\LoginForm;
use kosoukhov\ldap\wrappers\LdapWrapper;


/**
 * Base settings for Auth in LDAP
 *
 * @package kosoukhov\ldap\services
 * @author Kosoukhov V.E.
 */
class LdapAuth extends BaseObject
{

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
     * @var LdapWrapper
     */
    public $ldap;

    /**
     * @inheritdoc
     */
    public function authenticate(LoginForm $LoginForm)
    {
        return $this->ldap->checkAuth($LoginForm);
    }
}
