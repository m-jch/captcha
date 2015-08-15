<?php namespace Mohsen\Captcha;

use Session, Hash, URL, Config, Str, Crypt;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

class Captcha {

    public static function getImage($count = null, $width = null, $height = null, $backgroundColor = null, $quality = null)
    {
        $data = [];
        $data['count'] = isset($count) ? $count : 7;
        $data['width'] = isset($width) ? $width : 160;
        $data['height'] = isset($height) ? $height : 70;
        $data['backgroundcolor'] = isset($backgroundColor) ? $backgroundColor : 'efefef';
        $data['quality'] = isset($quality) && ($quality > 100 || $quality < 0) ? $quality : 50;
        $data['csrf_token'] = static::generateCsrf();

        $hashedUrl = Crypt::encrypt(http_build_query($data));

        return URL::to('/captcha/'.$hashedUrl);
    }

    public static function create($hashedUrl)
    {
        $url = Crypt::decrypt($hashedUrl);
        parse_str($url, $url);
        static::checkCsrf($url['csrf_token']);

        $captchaText = strtoupper(substr(Str::random(), 0, $url['count']));
        Session::put('captchaHash', Hash::make($captchaText));

        $image = imagecreate($url['width'], $url['height']);
        $background = imagecolorallocatealpha($image, hexdec(substr($url['backgroundcolor'], 0, 2)), hexdec(substr($url['backgroundcolor'], 2, 2)), hexdec(substr($url['backgroundcolor'], 4, 2)), 1);
        $textColor = imagecolorallocatealpha($image, 0, 0, 0, 1);

        $padding = $url['width'] / ($url['count'] + 2);
        $fixedFontSize = min($url['height'] / 2, $padding);

        for($i = 0; $i < $url['count']; $i++) {
            $fontSize = mt_rand(60, 100) * $fixedFontSize / 100;

            $x = $i * $padding + mt_rand(90, 140) * $padding / 100;
            $y = $url['height'] / 2 + mt_rand(80, 100) * ($fontSize / 2) / 100;

            $angle = mt_rand(5, 10);

            $text = $captchaText[$i];
            imagettftext($image, $fontSize, $angle, $x, $y, $textColor, __DIR__.'/../../../public/fonts/impact.ttf', $text);
        }

        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: image/jpeg');
        imagejpeg($image, null, $url['quality']);
        imagedestroy($image);
    }

    public static function validate($value)
    {
        if(Hash::check(strtoupper($value), Session::get('captchaHash'))) {
            return true;
        }
        return false;
    }

    protected static function generateCsrf()
    {
        $csrfTokenManger = new CsrfTokenManager();
        return $csrfTokenManger->getToken('login_csrf')->getValue();
    }

    protected static function checkCsrf($csrfValue)
    {
        $csrfTokenManger = new CsrfTokenManager();
        $csrfToken = new CsrfToken('login_csrf', $csrfValue);

        if (!$csrfTokenManger->isTokenValid($csrfToken)) {
            die;
        } else {
            $csrfTokenManger->removeToken('login_csrf');
        }
    }
}
