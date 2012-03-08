<?php

function picture_validate_q($picpath)
{
    $filesize = filesize($picpath);

    if ($filesize <= (45 * 1024)) {
        $return["quality"] = 100;
        $return["encode"] = "no";
    } elseif ($filesize <= (60 * 1024)) {
        $return["quality"] = 90;
        $return["encode"] = "yes";
    } else {
        $return["quality"] = 85;
        $return["encode"] = "yes";
    }

    return $return;
}

function resize_copy($img, $width, $height, $name, $inQuality)
{
    if (!$inQuality) {
        $inQuality = 85;
    }

    $imagedata = GetImageSize($img);

    if ($width) {
        $w = $width;
        $h = round($imagedata[1] / ($imagedata[0] / $width));
    }

    if ($height) {
        $w = round($imagedata[0] / ($imagedata[1] / $height));
        $h = $height;
    }

    $thumb = ImageCreateTrueColor($w, $h);
    $image = Picture_OpenRandomFormat($img, $imagedata["mime"]);

    ImageCopyResampled(
        $thumb,
        $image,
        0,
        0,
        0,
        0,
        $w,
        $h,
        $imagedata[0],
        $imagedata[1]
    );
    ImageJPEG($thumb, $name, $inQuality);
}

function picture_color_allocate($image, $hexcolor)
{
    $first = substr($hexcolor, 1, 2);
    $second = substr($hexcolor, 3, 2);
    $third = substr($hexcolor, 5, 2);

    $color = ImageColorAllocate(
        $image,
        hexdec($first),
        hexdec($second),
        hexdec($third)
    );

    $return["color"] = $color;
    $return["first"] = $first;
    $return["second"] = $second;
    $return["third"] = $third;

    return $return;
}

function picture_openrandomformat($image, $mime = false)
{
    if ($mime == false && mb_strtolower(mb_substr($image, 0, 7)) == "http://") {
        $last4 = mb_strtolower(mb_substr($image, -4));
        $last5 = mb_strtolower(mb_substr($image, -5));

        if ($last4 == ".jpg" || $last5 == ".jpeg") {
            $mime = "image/jpeg";
        } elseif ($last4 == ".gif") {
            $mime = "image/gif";
        } elseif ($last4 == ".bmp") {
            $mime = "image/bmp";
        } elseif ($last4 == ".png") {
            $mime = "image/png";
        }
    }

    if ($mime == false) {
        $mime = GetImageSize($image);
        $mime = $mime["mime"];
    }

    if ($mime == "image/jpeg") {
        return ImageCreateFromJPEG($image);
    } elseif ($mime == "image/gif") {
        return ImageCreateFromGIF($image);
    } elseif ($mime == "image/bmp") {
        require_once("knj/functions_knj_picture_bmp.php");
        return ImageCreateFromBMP($image);
    } elseif ($mime == "image/vnd.wbmp") {
        return ImageCreateFromWBMP($image);
    } elseif ($mime == "image/png") {
        return ImageCreateFromPNG($image);
    }

    return false;
}

function ImageOpen($img_location)
{
    return picture_openrandomformat($img_location);
}

function ImageOut($img, $type = "png", $quality = "85", $filename = null)
{
    if ($type == "png") {
        $quality = round($quality / 11, 0);
        return ImagePNG($img, $filename, $quality);
    } elseif ($type == "jpeg" || $type == "jpg") {
        return ImageJPEG($img, $filename, $quality);
    } elseif ($type == "gif") {
        return ImageGIF($img, $filename, $quality);
    } else {
        throw new exception("Invalid type: " . $type);
    }
}

function ImageRoundEdges($image, $size, $args = array())
{
    if (!is_numeric($size)) {
        throw new Exception("Not a valid size: " . $size);
    }

    $width = ImageSX($image);
    $height = ImageSY($image);

    if ($args["border"]) {
        $bordercolor = ImageHTMLColor($image, $args["border"]);
        $oldimage = $image;
        $image = ImageCreateTrueColor($width, $height);
        ImageFilledRectangle($image, 0, 0, $width, $height, $bordercolor);
        ImageCopyResized(
            $image,
            $oldimage,
            1,
            1,
            0,
            0,
            $width - 2,
            $height - 2,
            $width,
            $height
        );
    } else {
        $oldimage = $image;
        $image = ImageCreateTrueColor($width, $height);
        ImageCopyResized(
            $image,
            $oldimage,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $width,
            $height
        );
    }

    $transp = ImageColorTransparent($image);
    if (!$transp or $transp == -1) {
        if ($args["htmltranscolor"]) {
            $transp = ImageHTMLColor($image, $args["htmltranscolor"]);
        } else {
            $transp = ImageHTMLColor($image, "#d91da2");
        }
    }

    for ($i = 1; $i < $size / 2; $i++) {
        //Upper left corner.
        $center = $i - 2;
        ImageArc($image, $center, $center, $size, $size, 180, 270, $white);
        ImageArc($image, $center - 1, $center, $size, $size, 180, 270, $white);

        ImageArc($image, $center, $center, $size, $size, 180, 270, $transp);
        ImageArc($image, $center - 1, $center, $size, $size, 180, 270, $transp);


        //Upper right corner.
        $left = $width - $i + 2;
        $top = $i - 2;

        ImageArc($image, $left, $top, $size, $size, 270, 360, $transp);
        ImageArc($image, $left, $top - 1, $size, $size, 270, 360, $transp);


        //Lower left corner.
        $left = $i - 2;
        $top = $height - $i + 2;

        ImageArc($image, $left, $top, $size, $size, 90, 180, $transp);
        ImageArc($image, $left, $top - 1, $size, $size, 90, 180, $transp);


        //Lower right corner.
        $left = $width - $i + 2;
        $top = $height - $i + 2;

        ImageArc($image, $left, $top, $size, $size, 0, 90, $transp);
        ImageArc($image, $left, $top - 1, $size, $size, 0, 90, $transp);
    }

    if ($args["border"]) {
        //Lower right corner.
        $left = $width - $i + 2;
        $top = $height - $i + 2;
        ImageArc($image, $left, $top, $size, $size, 0, 90, $bordercolor);

        //Lower left corn
        $left = $i - 2;
        $top = $height - $i + 2;
        ImageArc($image, $left, $top, $size, $size, 90, 180, $bordercolor);

        //Upper left corner.
        $center = $i - 2;
        ImageArc($image, $center, $center - 1, $size, $size, 180, 270, $bordercolor);

        //Upper right corner.
        $left = $width - $i + 2;
        $top = $i - 2;
        ImageArc($image, $left, $top - 1, $size, $size, 270, 360, $bordercolor);
    }

    ImageColorTransparent($image, $transp);
    return $image;
}

function ImageSmartSize($image, $size)
{
    if (!$image) {
        throw new exception("Invalid image.");
    }

    $img_width = ImageSX($image);
    $img_height = ImageSY($image);

    if (is_numeric($size)) {
        if ($img_width > $img_height) {
            $sizetype = 0;
            $size_do = $img_width;

            $width = $size;
            $height = round($img_height / ($img_width / $width));
        } else {
            $sizetype = 1;
            $size_do = $height;

            $height = $size;
            $width = round($img_width / ($img_height / $height));
        }
    } elseif (is_array($size)) {
        if ($size["width"]) {
            $width = $size["width"];
        }

        if ($size["height"]) {
            $height = $size["height"];
        }

        if ($width && !$height) {
            $height = round($img_height / ($img_width / $width));
        }

        if ($height && !$width) {
            $width = round($img_width / ($img_height / $height));
        }
    } else {
        throw new exception("Invalid argument: " . gettype($size));
    }

    $thumb = ImageCreateTrueColor($width, $height);
    ImageAlphaBlending($thumb, false);
    ImageSaveAlpha($thumb, true);

    ImageCopyResampled(
        $thumb,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $img_width,
        $img_height
    );

    return $thumb;
}

function ImageHTMLColor($image, $color, $paras = array())
{
    if ($color[0] == "#") {
        $color = substr($color, 1);
    }

    if (strlen($color) == 6) {
        list($r, $g, $b) = array(
            $color[0] . $color[1],
            $color[2].$color[3],
            $color[4].$color[5]
        );
    } elseif (strlen($color) == 3) {
        list($r, $g, $b) = array(
            $color[0] . $color[0],
            $color[1] . $color[1],
            $color[2] . $color[2]
        );
    } else {
        throw new Exception("Invalid HTML-color-string length.");
    }

    if ($paras["alpha"]) {
        return ImageColorAllocateAlpha(
            $image,
            hexdec($r),
            hexdec($g),
            hexdec($b),
            127
        );
    }

    return ImageColorAllocate($image, hexdec($r), hexdec($g), hexdec($b));
}

function ImagePadding($paras)
{
    $image = $paras["image"];
    $color = $paras["color"];

    if ($paras["padding"]) {
        $padding_x = $paras["padding"];
        $padding_y = $paras["padding"];
    }

    if ($paras["padding_x"]) {
        $padding_x = $paras["padding_x"];
    }

    if ($paras["padding_y"]) {
        $padding_y = $paras["padding_y"];
    }

    $xcount = ImageSX($image);
    $ycount = ImageSY($image);

    if ($paras["keep_size"]) {
        $newxcount = $xcount;
        $newycount = $ycount;
    } else {
        $newxcount = $xcount + ($padding_x * 2);
        $newycount = $ycount + ($padding_y * 2);
    }

    $newimage = ImageCreateTrueColor($newxcount, $newycount);
    ImageAlphaBlending($newimage, false);
    ImageSaveAlpha($newimage, true);

    ImageFill($newimage, 0, 0, $color);

    if ($paras["keep_size"]) {
        $copyx = $xcount - ($padding_x * 2);
        $copyy = $ycount - ($padding_y * 2);
        ImageCopyResampled(
            $newimage,
            $image,
            $padding_x,
            $padding_y,
            0,
            0,
            $copyx,
            $copyy,
            $xcount,
            $ycount
        );
    } else {
        ImageCopyResampled(
            $newimage,
            $image,
            $padding_x,
            $padding_y,
            0,
            0,
            $xcount,
            $ycount,
            $xcount,
            $ycount
        );
    }

    return $newimage;
}

function ImageEqualSizes($paras)
{
    $image = $paras["image"];
    $color = $paras["color"];

    $xcount = ImageSX($image);
    $ycount = ImageSY($image);

    if ($xcount == $ycount) {
        return $image;
    }

    if ($xcount > $ycount) {
        $maxsize = $xcount;
        $dif = $xcount - $ycount;
        $start_x = 0;
        $start_y = round($dif / 2, 0);
    } else {
        $maxsize = $ycount;
        $dif = $ycount - $xcount;
        $start_y = 0;
        $start_x = round($dif / 2, 0);
    }

    $newimage = ImageCreateTrueColor($maxsize, $maxsize);
    ImageAlphaBlending($newimage, false);
    ImageSaveAlpha($newimage, true);

    ImageFill($newimage, 0, 0, $color);
    ImageCopyResampled(
        $newimage,
        $image,
        $start_x,
        $start_y,
        0,
        0,
        $xcount,
        $ycount,
        $xcount,
        $ycount
    );

    return $newimage;
}

function ImageResize($image, $paras)
{
    $xcount = ImageSX($image);
    $ycount = ImageSY($image);

    if ($paras["width"]) {
        $newwidth = $paras["width"];
    }

    if ($paras["height"]) {
        $newheight = $paras["height"];
    }

    if ($newwidth && !$newheight) {
        $newheight = round($ycount / ($xcount / $newwidth));
    }

    if ($newheight && !$newwidth) {
        $newwidth = round($xcount / ($ycount / $newheight));
    }

    $newimage = ImageCreateTrueColor($newwidth, $newheight);
    ImageAlphaBlending($newimage, false);
    ImageSaveAlpha($newimage, true);

    ImageCopyResampled(
        $newimage,
        $image,
        0,
        0,
        0,
        0,
        $newwidth,
        $newheight,
        $xcount,
        $ycount
    );
    return $newimage;
}

