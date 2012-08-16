<?php
require_once "functions_knj_os.php";

/**
 * Returns information about a specific networking-device.
 */
function network_ifconfig($args = null)
{
    //Make ifconfig-command.
    if (is_string($args)) {
        $command = "ifconfig " . $device;
    } else {
        $command = "ifconfig";
    }

    //Run command and catch result.
    if ($args["output"]) {
        $ipconfig = $args["output"];
    } else {
        $os = knj_os::getOS();
        if ($os["os"] != "linux") {
            throw new Exception("This command only works with Linux.");
        }

        $ipconfig = knj_os::shellCMD($command);
        $ipconfig = $ipconfig["result"];
    }

    //Parse result.
    if (preg_match_all("/([a-z]+[0-9]{0,2})\s+Link encap([\s\S]+)(\n\n|\r\n\r\n)/U", $ipconfig, $matches)) {
        foreach ($matches[0] as $key => $device_out) {
            $interface = $matches[1][$key];

            if (preg_match_all("/(R|T)X bytes:([0-9]+)/", $device_out, $match_bytes)) {
                $return[$interface]["rx_bytes"] = $match_bytes[2][0];
                $return[$interface]["tx_bytes"] = $match_bytes[2][1];
            }

            if (preg_match("/inet addr:([0-9.]{7,15})/", $device_out, $match_ip)) {
                $return[$interface]["ip"] = $match_ip[1];
            }

            if (preg_match("/Mask:([0-9.]{7,15})/", $device_out, $match_ip)) {
                $return[$interface]["mask"] = $match_ip[1];
            }

            if (preg_match("/Bcast:([0-9.]{7,15})/", $device_out, $match_ip)) {
                $return[$interface]["bast"] = $match_ip[1];
            }
        }
    }

    return $return;
}

