<?php

namespace kosoukhov\ldap\wrappers;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Wrapper for ping hosts
 *
 * @author VKosoukhov
 */
class SocketWrapper extends BaseObject
{

    /**
     * Check host is alive
     *
     * @param $host
     * @param int $port
     * @param int $timeout
     * @return int
     */
    public static function ping($host, $port = 389, $timeout = 1)
    {
        $op = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$op) {
            throw new InvalidConfigException('Socket error: could not connect to host ' . $host . ' on port ' . $port);
        } else {
            fclose($op); //explicitly close open socket connection
            return true; //DC is up & running, we can safely connect with ldap_connect
        }
    }
}