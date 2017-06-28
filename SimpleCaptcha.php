<?php
namespace VanXuan\SimpleCaptcha;

class SimpleCaptcha
{

//Captcha config
    var $cryptwidth = 130;
    var $cryptheight = 40;

    var $bgR = 255;
    var $bgG = 255;
    var $bgB = 255;

    var $bgclear = false;
    var $bgimg = '';

    var $bgframe = true;

    var $charR = 0;
    var $charG = 0;
    var $charB = 0;

    var $charcolorrnd = true;
    var $charcolorrndlevel = 2;

    var $charclear = 0;

    var $tfont = array('luggerbu.ttf');

    var $charel = '012345689';

    var $crypteasy = false;

    var $charelc = 'BCDFGKLMPRTVWXZ';
    var $charelv = 'AEIOUY';

    var $charnbmin = 6;
    var $charnbmax = 6;

    var $charspace = 18;
    var $charsizemin = 10;
    var $charsizemax = 18;

    var $charanglemax = 0;
    var $charup = false;
    var $ink;
    var $bg;

    var $cryptgaussianblur = false;

    var $cryptgrayscal = false;


    var $noisepxmin = 0;
    var $noisepxmax = 0;

    var $noiselinemin = 0;
    var $noiselinemax = 0;

    var $nbcirclemin = 0;
    var $nbcirclemax = 0;

    var $noisecolorchar = 2;
    var $brushsize = 1;
    var $noiseup = false;

    var $cryptformat = "png";
    var $cryptusetimer = 0;

    var $cryptusertimererror = 3;
    var $cryptusemax = 1000;

//Generate
    public function __construct()
    {
        if (isset($_SESSION['vanxuan.simplecaptcha.cryptcptuse']) && $_SESSION['vanxuan.simplecaptcha.cryptcptuse'] >= $this->cryptusemax) {
            header("Content-type: image/png");
            readfile(__DIR__ . '/images/erreur1.png');
            exit;
        }
        if (!isset($_SESSION['vanxuan.simplecaptcha.crypttime'])) $_SESSION['vanxuan.simplecaptcha.crypttime'] = time();
        $delai = time() - $_SESSION['vanxuan.simplecaptcha.crypttime'];
        if ($delai < $this->cryptusetimer) {
            switch ($this->cryptusertimererror) {
                case 2  :
                    header("Content-type: image/png");
                    readfile(__DIR__ . '/images/erreur2.png');
                    exit;
                case 3  :
                    sleep($this->cryptusetimer - $delai);
                    break; // Fait une pause
                case 1  :
                default :
                    exit;  // Quitte le script sans rien faire
            }
        }

        $imgtmp = imagecreatetruecolor($this->cryptwidth, $this->cryptheight);
        $blank = imagecolorallocate($imgtmp, 255, 255, 255);
        $black = imagecolorallocate($imgtmp, 0, 0, 0);
        imagefill($imgtmp, 0, 0, $blank);


        $word = '';
        $x = 10;
        $pair = rand(0, 1);
        $charnb = rand($this->charnbmin, $this->charnbmax);
        $tword = [];
        for ($i = 1; $i <= $charnb; $i++) {
            $tword[$i]['font'] = $this->tfont[array_rand($this->tfont, 1)];
            $tword[$i]['angle'] = (rand(1, 2) == 1)
                ? rand(0, $this->charanglemax) : rand(360 - $this->charanglemax, 360);

            if ($this->crypteasy) $tword[$i]['element'] = (!$pair)
                ? $this->charelc{rand(0, strlen($this->charelc) - 1)}
                : $this->charelv{rand(0, strlen($this->charelv) - 1)};
            else $tword[$i]['element'] = $this->charel{rand(0, strlen($this->charel) - 1)};

            $pair = !$pair;
            $tword[$i]['size'] = rand($this->charsizemin, $this->charsizemax);
            $tword[$i]['y'] = ($this->charup ? ($this->cryptheight / 2) + rand(0, ($this->cryptheight / 5)) : ($this->cryptheight / 1.5));
            $word .= $tword[$i]['element'];

            $lafont = __DIR__ . "/fonts/" . $tword[$i]['font'];
            imagettftext($imgtmp, $tword[$i]['size'], $tword[$i]['angle'], $x, $tword[$i]['y'], $black, $lafont, $tword[$i]['element']);

            $x += $this->charspace;
        }
        $this->tword = $tword;

// Calcul du racadrage horizontal du cryptogramme temporaire
        $xbegin = 0;
        $x = 0;
        while (($x < $this->cryptwidth) and (!$xbegin)) {
            $y = 0;
            while (($y < $this->cryptheight) and (!$xbegin)) {
                if (imagecolorat($imgtmp, $x, $y) != $blank) $xbegin = $x;
                $y++;
            }
            $x++;
        }

        $xend = 0;
        $x = $this->cryptwidth - 1;
        while (($x > 0) and (!$xend)) {
            $y = 0;
            while (($y < $this->cryptheight) and (!$xend)) {
                if (imagecolorat($imgtmp, $x, $y) != $blank) $xend = $x;
                $y++;
            }
            $x--;
        }

        $this->xvariation = round(($this->cryptwidth / 2) - (($xend - $xbegin) / 2));
        imagedestroy($imgtmp);

        $img = imagecreatetruecolor($this->cryptwidth, $this->cryptheight);

        if ($this->bgimg and is_dir($this->bgimg)) {
            $dh = opendir($this->bgimg);
            while (false !== ($filename = readdir($dh)))
                if (preg_match(".[gif|jpg|png]$", $filename)) $this->files[] = $filename;
            closedir($dh);
            $this->bgimg = $this->bgimg . '/' . $this->files[array_rand($this->files, 1)];
        }
        if ($this->bgimg) {
            list($getwidth, $getheight, $gettype,) = getimagesize($this->bgimg);
            $imgread = 0;
            switch ($gettype) {
                case "1":
                    $imgread = imagecreatefromgif($this->bgimg);
                    break;
                case "2":
                    $imgread = imagecreatefromjpeg($this->bgimg);
                    break;
                case "3":
                    $imgread = imagecreatefrompng($this->bgimg);
                    break;
            }
            if ($imgread) {
                imagecopyresized($img, $imgread, 0, 0, 0, 0, $this->cryptwidth, $this->cryptheight, $getwidth, $getheight);
                imagedestroy($imgread);
            }
        } else {
            $this->bg = imagecolorallocate($img, $this->bgR, $this->bgG, $this->bgB);
            imagefill($img, 0, 0, $this->bg);
            if ($this->bgclear) imagecolortransparent($img, $this->bg);
        }

        $this->img = $img;


        if ($this->noiseup) {
            $this->ecriture();
            $this->bruit();
        } else {
            $this->bruit();
            $this->ecriture();
        }

        if ($this->bgframe) {
            $framecol = imagecolorallocate($img, ($this->bgR * 3 + $this->charR) / 4, ($this->bgG * 3 + $this->charG) / 4, ($this->bgB * 3 + $this->charB) / 4);
            imagerectangle($img, 0, 0, $this->cryptwidth - 1, $this->cryptheight - 1, $framecol);
        }

        if (function_exists('imagefilter')) {
            if ($this->cryptgrayscal) imagefilter($img, IMG_FILTER_GRAYSCALE);
            if ($this->cryptgaussianblur) imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $word = strtoupper($word);

        $_SESSION['vanxuan.simplecaptcha.cryptcode'] = md5($word);
        $_SESSION['vanxuan.simplecaptcha.crypttime'] = time();
        if (!isset($_SESSION['vanxuan.simplecaptcha.cryptcptuse']))
            $_SESSION['vanxuan.simplecaptcha.cryptcptuse'] = 0;
        $_SESSION['vanxuan.simplecaptcha.cryptcptuse']++;

// Envoi de l'image finale au navigateur
        switch (strtoupper($this->cryptformat)) {
            case "JPG"  :
            case "JPEG" :
                if (imagetypes() & IMG_JPG) {
                    header("Content-type: image/jpeg");
                    imagejpeg($img, "", 80);
                }
                break;
            case "GIF"  :
                if (imagetypes() & IMG_GIF) {
                    header("Content-type: image/gif");
                    imagegif($img);
                }
                break;
            case "PNG"  :
            default     :
                if (imagetypes() & IMG_PNG) {
                    header("Content-type: image/png");
                    imagepng($img);
                }
        }

        imagedestroy($img);
        unset ($word, $tword);
        die();
    }

    private function ecriture()
    {
        if (function_exists('imagecolorallocatealpha'))
            $this->ink = imagecolorallocatealpha($this->img, $this->charR, $this->charG, $this->charB, $this->charclear);
        else
            $this->ink = imagecolorallocate($this->img, $this->charR, $this->charG, $this->charB);

        $x = $this->xvariation;
        $charnb = rand($this->charnbmin, $this->charnbmax);
        for ($i = 1; $i <= $charnb; $i++) {
            $rndink = 0;
            if ($this->charcolorrnd) {   // Choisit des couleurs au hasard
                $ok = false;
                do {
                    $rndR = rand(0, 255);
                    $rndG = rand(0, 255);
                    $rndB = rand(0, 255);
                    $rndcolor = $rndR + $rndG + $rndB;
                    switch ($this->charcolorrndlevel) {
                        case 1  :
                            if ($rndcolor < 200) $ok = true;
                            break; // tres sombre
                        case 2  :
                            if ($rndcolor < 400) $ok = true;
                            break; // sombre
                        case 3  :
                            if ($rndcolor > 500) $ok = true;
                            break; // claires
                        case 4  :
                            if ($rndcolor > 650) $ok = true;
                            break; // tr�s claires
                        default :
                            $ok = true;
                    }
                } while (!$ok);

                if (function_exists('imagecolorallocatealpha'))
                    $rndink = imagecolorallocatealpha($this->img, $rndR, $rndG, $rndB, $this->charclear);
                else
                    $rndink = imagecolorallocate($this->img, $rndR, $rndG, $rndB);
            }

            $lafont = __DIR__ . "/fonts/" . $this->tword[$i]['font'];
            imagettftext($this->img, $this->tword[$i]['size'], $this->tword[$i]['angle'], $x, $this->tword[$i]['y'], $this->charcolorrnd ? $rndink : $this->ink, $lafont, $this->tword[$i]['element']);

            $x += $this->charspace;
        }
    }

    private function noisecolor()
    {
        switch ($this->noisecolorchar) {
            case 1  :
                $noisecol = $this->ink;
                break;
            case 2  :
                $noisecol = $this->bg;
                break;
            case 3  :
            default :
                $noisecol = imagecolorallocate($this->img, rand(0, 255), rand(0, 255), rand(0, 255));
                break;
        }
        if ($this->brushsize and $this->brushsize > 1 and function_exists('imagesetbrush')) {
            $brush = imagecreatetruecolor($this->brushsize, $this->brushsize);
            imagefill($brush, 0, 0, $noisecol);
            imagesetbrush($this->img, $brush);
            $noisecol = IMG_COLOR_BRUSHED;
        }
        return $noisecol;
    }

    // Ajout de bruits: point, lignes et cercles al�atoires
    private function bruit()
    {
        $nbpx = rand($this->noisepxmin, $this->noisepxmax);
        $nbline = rand($this->noiselinemin, $this->noiselinemax);
        $nbcircle = rand($this->nbcirclemin, $this->nbcirclemax);
        for ($i = 1; $i < $nbpx; $i++) imagesetpixel($this->img, rand(0, $this->cryptwidth - 1), rand(0, $this->cryptheight - 1), $this->noisecolor());
        for ($i = 1; $i <= $nbline; $i++) imageline($this->img, rand(0, $this->cryptwidth - 1), rand(0, $this->cryptheight - 1), rand(0, $this->cryptwidth - 1), rand(0, $this->cryptheight - 1), $this->noisecolor());
        for ($i = 1; $i <= $nbcircle; $i++) imagearc($this->img, rand(0, $this->cryptwidth - 1), rand(0, $this->cryptheight - 1), $rayon = rand(5, $this->cryptwidth / 3), $rayon, 0, 360, $this->noisecolor());
    }

    public static function check($code)
    {
        $code = addslashes($code);
        $code = str_replace(' ', '', $code);
        $code = strtoupper($code);
        $code = md5($code);
        return isset($_SESSION['vanxuan.simplecaptcha.cryptcode']) && ($_SESSION['vanxuan.simplecaptcha.cryptcode'] == $code);
    }
}
