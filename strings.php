<?php
/**
 * This file contains the knj_strings class
 *
 * PHP version 5
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */

/**
 * Class for handeling several common string processesing tasks
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class knj_strings
{
    /**
     * Convert any string to UTF8
     *
     * @param string $string String to process
     *
     * @return string
     */
    static function utf8force($string)
    {
        $encoding = mb_detect_encoding($string);
        if ($encoding != 'UTF-8') {
            if (!$encoding || $encoding == 'ISO-8859-1') {
                $encoding = 'windows-1252';
            }
            return mb_convert_encoding($string, 'UTF-8', $encoding);
        }

        return $string;
    }

    /**
     * Parses a string into an array of strings, which should all be searched for.
     *
     * @param string $string String to process
     *
     * @return array TODO
     */
    static function searchstring($string)
    {
        $array = array();

        if (preg_match_all("/\"(.*)\"/U", $string, $matches)) {
            foreach ($matches[1] as $key => $value) {
                $array[] = $value;
                $string = str_replace($matches[0][$key], "", $string);
            }
        }

        if (mb_strlen($string) > 0) {
            foreach (preg_split("/\s/", $string) as $value) {
                if (mb_strlen(trim($value)) > 0) {
                    $array[] = $value;
                }
            }
        }

        return $array;
    }

    /**
     * TODO
     *
     * @param string $content TODO
     *
     * @return string TODO
     */
    static function parseImageHTML($content)
    {
        if (preg_match_all("/<img [\s\S]+ \/>/U", $content, $matches)) {
            foreach ($matches[0] as $key => $value) {
                $img_html = $value;

                if (preg_match("/src=\"([\s\S]+)\"/U", $img_html, $match_src)) {
                    $src = $match_src[1];
                    if (mb_substr($src, 0, 1) == "/") {
                        $src = mb_substr($src, 1);
                    }

                    $replace_with = "image.php?picture=" .$src;

                    if (preg_match("/width: ([0-9]+)(px|)/", $img_html, $match_width)) {
                        $size["width"] = $match_width[1];
                        $replace_with .= "&width=" .$match_width[1];
                    }

                    if (preg_match("/height: ([0-9]+)(px|)/", $img_html, $match_height)) {
                        $size["height"] = $match_height[1];
                        $replace_with .= "&height=" .$match_width[1];
                    }

                    if (preg_match_all("/(width|height)=\"([0-9]+)(px|)\"/", $img_html, $match_sizes)) {
                        $size = array();
                        foreach ($match_sizes[1] as $key => $sizetype) {
                            if (!$size[$sizetype]) {
                                $size[$sizetype] = $match_sizes[2][$key];
                                $replace_with .= "&" .$sizetype ."=" .$match_sizes[2][$key];
                            }
                        }
                    }

                    if ($size) {
                        $img_html = str_replace($src, $replace_with, $img_html);
                        $content = str_replace($value, $img_html, $content);
                    }
                }
            }
        }

        return $content;
    }


    /**
     * Parse a string so it fits into the command-line of Linux.
     *
     * @param string $string string to process
     *
     * @return string
     */
    static function UnixSafe($string)
    {
        return addcslashes($string, "\\ \$();,'><\"&");
    }

    /**
     * Escape alle chars with special meaning in a regulare expression
     *
     * @param string $string string to process
     *
     * @return string
     */
    static function RegexSafe($string)
    {
        return addcslashes($string, '\\/.()[]^\$+');
    }

    /**
     * Strip all new lines from string
     *
     * @param string $string string to process
     *
     * @return string
     */
    static function HeaderSafe($string)
    {
        $replace = array(
            "\r\n" => " ",
            "\n" => " ",
            "\r" => " "
        );
        return strtr($string, $replace);
    }

    /**
     * Escape chars that can break a string in JavaScript
     *
     * @param string $string String to process
     * @param bool   $quotes Weather to also escape douple quotes
     *
     * @return string
     */
    static function jsparse($string, $quotes = false)
    {
        $replace = array(
            "'" => "\\'",
            "\r\n" => "\\n",
            "\n" => "\\n",
            "\r" => "\\n"
        );
        $string = strtr($string, $replace);

        if ($quotes) {
            $string = str_replace('"', '\\"', $string);
        }

        return $string;
    }

    /**
     * Crope a string to a specific length
     *
     * @param string $text      Input string
     * @param string $maxlength The decired length
     *
     * @return string
     */
    static function shorten($string, $maxlength = 0, $ellipsis = '…')
    {
        $string = trim($string);

        if (!$maxlength || mb_strlen($string) <= $maxlength) {
            return $string;
        }

        $string .= ' ';
        $string = mb_substr($string, 0, $maxlength - mb_strlen($ellipsis));
        $string = preg_replace('/\s+\S*$/u', ' ', $string);

        return $string . $ellipsis;
    }

    /**
     * Test if a string is a valid email
     *
     * @param string $str String to test
     *
     * @return bool Returns true if it is a valid email, false if not
     */
    static function is_email($str)
    {
        if (preg_match("/^(.+)@(.+)\.(.+)/", $str)) {
            return true;
        }

        return false;
    }

    /**
     * Convert chars with accents to nearest equvilant
     *
     * @param string $string String to process
     *
     * @return string
     */
    static function unaccent($string)
    {
        $charmap = array(
            "ø" => "oe",
            "å" => "aa",
        );
        $string = strtr($string, $charmap);

        $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
        $string = preg_replace(
            '/&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
            '$1',
            $string
        );
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

        return $string;
    }

    /**
     * Parse a string so it will be a valid filename.
     *
     * @param string $filename String to process
     * @param string $so       OS to comply with.
     *
     * @return string
     */
    static function filename_safe($filename, $os = null)
    {
        if (!$os) {
            include_once "knj/os.php";
            $os = knj_os::getOS();
            $os = $os["os"];
        }

        if ($os == "windows") {
            $search  = '/["*:<>?\|]+/u';
            $replace = '';
        } elseif ($os == "linux") {
            $search  = '#/#u';
            $replace = '';
        } elseif ($os == "posix") {
            $string = unaccent($string);
            $search  = '/[^A-z0-9._-]+/u';
            $replace = '';
        } else {
            throw new Exception("Unsupported OS.");
        }

        return preg_replace($search, $replace, $string);
    }
}

