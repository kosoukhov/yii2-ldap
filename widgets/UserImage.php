<?php

namespace kosoukhov\ldap\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * UserImage displays a user image from Active Directory.
 *
 * The main property of Menu is [[login]], which specifies the user smAccountName in LDAP.
 *
 *
 * The following example shows how to use UserImage:
 *
 * ```php
 * echo UserImage::widget([
 *     'login' => 'userLogin'
 *     ],
 * ]);
 * ```
 *
 * @package kosoukhov\ldap\forms
 * @author Kosoukhov V.E.
 */
class UserImage extends Widget
{

    /**
     * @var string user login (smAccountame in AD)
     */
    public $login;

    /**
     * @var array the HTML attributes for the img tag. The following special options are recognized:
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];

    public function init()
    {
        parent::init();
    }

    /**
     * Image tag with data
     * @return string|void
     */
    public function run()
    {
        echo Html::img($this->getUserImage(), $this->options);
    }

    /**
     * UserImage data from cache or LDAP
     * @return mixed|string
     */
    protected function getUserImage()
    {
        if(Yii::$app->ldap->useCache){
            $key = 'ldap_user_image_' . $this->login;
            $out = Yii::$app->cache->get($key);
            if (!$out) {
                $out = $this->getLdapImage();

                Yii::$app->cache->set($key, $out, Yii::$app->ldap->cacheDuration);
            }
        } else {
            $out = $this->getLdapImage();
        }

        return $out;
    }

    /**
     * UserImage data from LDAP
     * @return string
     */
    protected function getLdapImage()
    {
        $ldapImage = Yii::$app->ldap->searchUserByLogin($this->login, ['jpegphoto'])->getModels();
        $imageString = $ldapImage[0]['jpegphoto'];

        $tempFile = tempnam(sys_get_temp_dir(), 'image');
        file_put_contents($tempFile, $imageString);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = explode(';', $finfo->file($tempFile));

        $content = 'data:' . $mime[0] . ';base64,' . base64_encode($imageString);

        return $content;
    }
}
