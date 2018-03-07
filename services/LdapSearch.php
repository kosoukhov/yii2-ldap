<?php

namespace kosoukhov\ldap\services;

use yii\base\BaseObject;
use kosoukhov\ldap\wrappers\LdapWrapper;


/**
 * Search users in LDAP
 *
 * @author Kosoukhov V.E.
 * @package kosoukhov\ldap\services
 */
class LdapSearch extends BaseObject
{
    /**
     * @var string
     */
    public $baseDN = '';

    /**
     * @var LdapWrapper
     */
    public $ldap;

    /**
     * Get user attributes by sAMAccountName.
     *
     * @param string $sAMAccountName
     * @param array $attributes
     * @return array|bool
     */
    public function getUserAttributesBySAMAccountName($sAMAccountName, $attributes)
    {
        return $this->normaizeAttributes($this->getAttributesByIdUserAttribute('sAMAccountName', $sAMAccountName, $attributes), $attributes);
    }

    /**
     * Normalize array structure from AD
     * @param $data
     * @return mixed
     */
    protected function normaizeAttributes($data, $attributes)
    {
        $ret = [];
        for ($i = 0; $i < $data['count']; $i++) {
            foreach ($attributes as $v) {
                $ret[$i][$v] = (isset($data[$i][$v]) ? $data[$i][$v][0] : null);
            }
        }

        return $ret;
    }

    /**
     * Get user attributes by id user attribute.
     *
     * @param string $idUserAttributeName
     * @param string $idUserAttributeValue
     * @param string|array $attributes
     * @return array|boolean
     */
    protected function getAttributesByIdUserAttribute($idUserAttributeName, $idUserAttributeValue, $attributes, $type = 'user')
    {
        $data = [];

        if (!is_array($attributes)) {
            $singleAttribute = $attributes;
            $attributes = [];
            $attributes[] = $singleAttribute;
        }

        $filter = "(&(objectClass=" . $type . ")(" . $idUserAttributeName . "=" . $this->escapeFilterValue($idUserAttributeValue) . "))";

        $res = $this->ldap->search($this->baseDN, $filter, $attributes);

        if ($res) {

            $data = ldap_get_entries($this->ldap->ldapConnection, $res);

            if ($data['count'] >= 1) {
                if (isset($singleAttribute)) {
                    return $data[0][strtolower($singleAttribute)][0];
                }
                return $data;
            }
        }
    }

    /**
     * Escape values for use in LDAP filters.
     *
     * @param string $s
     */
    protected function escapeFilterValue($s)
    {
        return str_replace(array('(', ')', '\\,'), array('\28', '\29', '\\\\,'), $s);
    }

    /**
     * Get user attributes by email.
     *
     * @param string $email
     * @param array $attributes
     * @return array|bool
     */
    public function getUserAttributesByEmail($email, $attributes)
    {
        return $this->normaizeAttributes($this->getAttributesByIdUserAttribute('mail', $email, $attributes), $attributes);
    }
}