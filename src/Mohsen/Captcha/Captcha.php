<?php namespace Mohsen\Captcha;

use Session, Hash, URL, Config, Str, Crypt;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

class Captcha {

    public static function getImage($count = null, $width = null, $height = null, $backgroundColor = null, $quality = null, $stringType = null)
    {
        $data = [];
        $data['count'] = $count ?: 7;
        $data['width'] = $width ?: 160;
        $data['height'] = $height ?: 70;
        $data['backgroundcolor'] = $backgroundColor ?: 'efefef';
        $data['quality'] = $quality && ($quality > 100 || $quality < 0) ? $quality : 50;
        $data['csrf_token'] = static::generateCsrf();
        $data['string_type'] = $stringType ?: 'mixedLowercase';

        $hashedUrl = Crypt::encrypt(http_build_query($data));

        return URL::to('/captcha/'.$hashedUrl);
    }

    public static function create($hashedUrl)
    {
        parse_str(Crypt::decrypt($hashedUrl), $url);
        static::checkCsrf($url['csrf_token']);

        $captchaText = static::getRandomString($url['string_type'], $url['count']);
        Session::put('captchaHash', Hash::make($captchaText));

        $image = imagecreate($url['width'], $url['height']);
        $background = imagecolorallocatealpha($image, hexdec(substr($url['backgroundcolor'], 0, 2)), hexdec(substr($url['backgroundcolor'], 2, 2)), hexdec(substr($url['backgroundcolor'], 4, 2)), 1);
        $textColor = imagecolorallocatealpha($image, 0, 0, 0, 1);

        $padding = $url['width'] / ($url['count'] + 2);
        $fixedFontSize = min($url['height'] / 2, $padding);

        for($i = 0; $i < $url['count']; $i++) {
            $fontSize = mt_rand(90, 130) * $fixedFontSize / 100;

            $x = $i * $padding + mt_rand(90, 140) * $padding / 100;
            $y = $url['height'] / 2 + mt_rand(100, 130) * ($fontSize / 2) / 100;

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

    public static function getRandomString($type, $count)
    {
        switch ($type) {
            case 'alphabetOnly':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numberOnly':
                $pool = '0123456789';
                break;
            case 'mixedUppercase':
                $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'mixedLowercase':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyz';
                break;
            case 'mixed':
            default:
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }

        return substr(str_shuffle(str_repeat($pool, $count)), 0, $count);
    }
}
