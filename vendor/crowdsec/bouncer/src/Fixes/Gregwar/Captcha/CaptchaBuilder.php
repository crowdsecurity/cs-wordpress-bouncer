<?php

namespace CrowdSecBouncer\Fixes\Gregwar\Captcha;

use Gregwar\Captcha\CaptchaBuilder as GregwarCaptchaBuilder;

/**
 * Override to :
 * - fix "implicit conversion error on PHP  8.1"
 * - fix "creation of dynamic property $background error on PHP 8.2"
 *
 *
 * @see https://github.com/crowdsecurity/php-cs-bouncer/issues/62 and
 * @see https://github.com/Gregwar/Captcha/pull/101/files
 * @SuppressWarnings(PHPMD.ElseExpression)
 *
 */
class CaptchaBuilder extends GregwarCaptchaBuilder
{
    /**
     * @var false|int
     */
    protected $background = false;
    /**
     * Writes the phrase on the image
     */
    protected function writePhrase($image, $phrase, $font, $width, $height)
    {
        $length = mb_strlen($phrase);
        if ($length === 0) {
            return \imagecolorallocate($image, 0, 0, 0);
        }

        // Gets the text size and start position
        $size = (int) round($width / $length) - $this->rand(0, 3) - 1;
        $box = \imagettfbbox($size, 0, $font, $phrase);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) round(($width - $textWidth) / 2);
        $y = (int) round(($height - $textHeight) / 2) + $size;

        if (!$this->textColor) {
            $textColor = array($this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150));
        } else {
            $textColor = $this->textColor;
        }
        $col = \imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);

        // Write the letters one by one, with random angle
        for ($i = 0; $i < $length; $i++) {
            $symbol = mb_substr($phrase, $i, 1);
            $box = \imagettfbbox($size, 0, $font, $symbol);
            $w = $box[2] - $box[0];
            $angle = $this->rand(-$this->maxAngle, $this->maxAngle);
            $offset = $this->rand(-$this->maxOffset, $this->maxOffset);
            \imagettftext($image, $size, $angle, $x, $y + $offset, $col, $font, $symbol);
            $x += $w;
        }

        return $col;
    }


    /**
     * Returns a random number or the next number in the
     * fingerprint
     */
    protected function rand($min, $max)
    {
        if (!is_array($this->fingerprint)) {
            $this->fingerprint = array();
        }

        if ($this->useFingerprint) {
            $value = current($this->fingerprint);
            next($this->fingerprint);
        } else {
            $value = mt_rand((int) $min, (int) $max);
            $this->fingerprint[] = $value;
        }

        return $value;
    }
}
