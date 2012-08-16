<?php

class knj_mail
{
    static function get_body($imap, $msg_no)
    {
        return knjimap_getBody($imap, $msg_no);
    }

    static function replace($string)
    {
        return knjimap_replace($string);
    }

    static function parse($message, $struc)
    {
        return knjimap_parse($message, $struc);
    }
}

/**
 * This function reads the body of an imap-text returning only the pure text of the email - stripping HTML-tags if present.
 */
function knjimap_getBody($imap, $msg_no)
{
    $struc = imap_fetchstructure($imap, $msg_no) or throwexception('Could not fetch the structure.');
    $body = knjimap_getBodyRec($imap, $struc, $msg_no);

    if (!$body) {
        throw new Exception('Could not find any body.');
    }

    if (strpos($body, 'Content-Type: ') !== false || strpos($body, 'User-Agent: ') !== false) {
        $pos = strpos($body, "\r\n\r\n");
        $body = substr($body, $pos);
    }

    return trim(knjimap_replace($body));
}

/**
 * This function is used by knjimap_getBody() to recursivly scan the parts of an imap-message for valid text-content.
 */
function knjimap_getBodyRec($imap, $struc, $msg_no, $part_no = null)
{
    if ($struc->type == 0) {
        $body = imap_fetchbody($imap, $msg_no, $part_no);
        $body = knjimap_parse($body, $struc);

        if ($struc->subtype == 'PLAIN') {
            //do nothing
        } elseif ($struc->subtype == 'HTML') {
            $body = strip_tags($body);
        } else {
            $msg = sprintf(
                _('Warning: Unknown subtype: %s.'),
                $struc->subtype
            );
            echo($msg . "\n");
        }

        if ($body) {
            $body = knjimap_replace(strip_tags($body));

            $paras = array();
            foreach ($struc->parameters as $para) {
                $paras[$para->attribute] = $para->value;
            }

            if ($paras['CHARSET']) {
                $body = iconv($paras['CHARSET'], 'utf-8', $body);
            }
        }

        return $body;
    } elseif ($struc->type == 1) {
        $count = 0;
        foreach ($struc->parts as $part) {
            $count++;

            $tha_part_no = null;
            if (strlen($tha_part_no) >= 1) {
                $tha_part_no .= '.';
            }
            $tha_part_no .= $count;

            $body = knjimap_getBodyRec(
                $imap,
                $part,
                $msg_no,
                $tha_part_no
            );

            if ($body) {
                return $body;
            }
        }
    }
}

/**
 * Parses a string.
 */
function knjimap_parseHeader($string)
{
    $object = imap_mime_header_decode($string);
    return knjimap_replace($object[0]->text);
}

function knjimap_replace($string)
{
    return strtr($string, array(
        "=F8" => "ø",
        "=E5" => "å",
        "=E6" => "æ",
        "=20" => " ",
        "=5F" => "_",
        "=\r\n" => "\r\n",
        "&#39;" => "'",
        "=C3=A6" => "æ",
        "=C3=B8" => "ø",
        "=C3=A5" => "å",
        "=2E" => ".",
        "#39;" => "'",
        "&nbsp;" => " "
    ));
}

/**
 * This function helps the knjimap_getBody()-family to parse content.
 */
function knjimap_parse($message, $struc)
{
    $coding = $struc->parts[$part]->encoding;

    if ($coding == 0) {
        //$message = imap_7bit($message); //this function does not exist??
    } elseif ($coding == 1) {
        $message = imap_8bit($message);
    } elseif ($coding == 2) {
        $message = imap_binary($message);
    } elseif ($coding == 3) {
        $message = imap_base64($message);
    } elseif ($coding == 4) {
        $message = quoted_printable($message);
    } elseif ($coding == 5) {
        //$message = $message;
    }

    return $message;
}

