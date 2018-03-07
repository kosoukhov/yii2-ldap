<?php

namespace kosoukhov\ldap\exceptions;

use yii\base\Exception;


/**
 * Class LdapException
 *
 * @package kosoukhov\ldap\exceptions
 * @author Kosoukhov V.E.
 */
class LdapException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Ldap Exception';
    }
}
