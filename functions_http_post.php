<?php
function http_post_urlarray($ident, $keys, $array)
{
    $in_first = true;

    foreach ($array as $key => $value) {
        if (substr($return, -1) == "&") {
            $return = substr($postdata, 0, -1);
        }

        if ($in_first == true) {
            $in_first = false;
        } else {
            $return .= "&";
        }

        if (is_array($value)) {
            $keys = $keys;
            $keys[] = $key;

            $return .= sms_post_urlarray($ident, $keys, $value);
        } else {
            $return .= $ident;

            if ($keys) {
                foreach ($keys as $thiskey) {
                    $return .= "[" . urlencode($thiskey) . "]";
                }
            }

            $return .= "[" . urlencode($key) . "]=" . urlencode($value);
        }
    }

    $return .= "&";

    return $return;
}

function http_post($host, $port, $site, $options)
{
    $fp = fsockopen($host, $port, $error_number, $error_string, 2);

    if ($fp) {
        $in_first = true;

        foreach ($options as $key => $value) {
            if ($in_first == true) {
                $in_first = false;
            } else {
                $postdata .= "&";
            }

            if (is_array($value)) {
                $in_first = true;
                $postdata .= http_post_urlarray($key, "", $value);
            } else {
                $postdata .= urlencode($key) . "=" . urlencode($value);
            }
        }

        $out = "POST http://" . $host . "/" . $site . " HTTP/1.0\r\n";
        $out .= "Content-Length: " . strlen($postdata) . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "\r\n";
        $out .= $postdata;

        fwrite($fp, $out);

        $in_answer = "";

        while (!feof($fp)) {
            $in_string = fgets($fp, 1024);
            $in_answer .= $in_string;
        }

        fclose($fp);

        return $in_answer;
    } else {
        echo "No connection.";
    }
}

function http_post_file($host, $port, $site, $file)
{
    $boundary = "---mYbOun";
    $cont = file_get_contents($file);
    $info = pathinfo($file);

    $post .= "--" . $boundary . "\n";
    $post .= "Content-Disposition: form-data; name=\"" . htmlspecialchars($info["filename"]) . "\"; filename=\"" . htmlspecialchars($info["basename"]) . "\"\n";
    $post .= "Content-Type: application/octet-stream\n\n";
    $post .= $cont;
    $post .= "\n--" . $boundary . "--\n\n";

    $headers .= "POST http://" . $host . "/" . $site . " HTTP/1.0\n";
    $headers .= "Content-Type: multipart/form-data; boundary=" . $boundary . "\n";
    $headers .= "Content-Length: " . strlen($post) . "\n";
    $headers .= "\n";

    $sendd = $headers . $post;
    $length = strlen($sendd);
    unset($post);
    unset($headers);

    $fp = fsockopen($host, 80);
    if (!$fp) {
        echo "No connection.\n";
        return null;
    }

    while ($count < ($length + 2048)) {
        fputs($fp, substr($sendd, $count, 2048));
        $count += 2048;
    }

    while (!feof($fp)) {
        fread($fp, 1024);
    }

    fclose($fp);
}

