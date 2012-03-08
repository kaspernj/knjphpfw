<?php
/**
 * Destroys a object. If the object's objects contains destroy-methods, these will be called.
 * Unsetting alle variables to free memory.
 *
 * @param Object $obj The object which should be free'ed.
 */
function destroy_obj($obj)
{
    if (!is_object($obj)) {
        throw new Exception(gtext("destroy_obj(): The given argument is not a object."));
    }

    $arr = get_object_vars($obj);
    if (is_array($arr)) {
        foreach ($arr as $key => $value) {
            if (is_object($value)) {
                //if (method_exists($value, "destroy")) {
                    //$value->destroy();
                //}

                unset($value);
                unset($obj->$key);
            } else {
                unset($obj->$key);
            }
        }
    }
    unset($obj);
}

