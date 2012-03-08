<?php
/**
 * This function "emulates" gettext - it does not translate anything, but runs the program without crashing because the gettext()-function does not exist.
 */
function gettext($text)
{
    return $text;
}

