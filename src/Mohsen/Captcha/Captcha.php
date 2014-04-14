<?php namespace Mohsen\Captcha;
use Session, Hash, URL;

class Captcha {
 
  public static function create(){
    $captchaText = (string) mt_rand(100000, 999999); strtoupper(substr(md5(microtime()), 0, 7));
    Session::put('captchaHash', Hash::make($captchaText));

    $image = imagecreate(160, 70);
    $background = imagecolorallocatealpha($image, 239, 239, 239, 1);
    $textColor = imagecolorallocatealpha($image, 0, 0, 0, 1);
    $x = 5;
    $y = 50;
    $angle = 0;

    for($i = 0; $i < 7; $i++) {
      $fontSize = mt_rand(15, 35);
      $text = substr($captchaText, $i, 1);
      imagettftext($image, $fontSize, $angle, $x, $y, $textColor, __DIR__ . '/../../../public/fonts/impact.ttf', $text);

      $x = $x + 17 + mt_rand(1, 10);
      $y = mt_rand(40, 65);
      $angle = mt_rand(0, 10);
    }

    header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Pragma: no-cache');
		header('Content-type: image/jpeg');
    imagejpeg($image, null, 100);
    imagedestroy($image);
  }
  
  public static function validate($value) {
    if(Hash::check($value, Session::get('captchaHash'))) {
      return true;
    }
    return false;
  }
}