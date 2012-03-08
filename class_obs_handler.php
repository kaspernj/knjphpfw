<?php
/**
 * This class can remember a list of objects, so only one instance of them will be created (if they have the same ID).
 */
class ObsHandeler
{
    private $list_obs;

    /**
     * Adds a new object to the list.
     */
    function add($id, $ob)
    {
        $this->list_obs[$id] = $ob;
    }

    /**
     * Removes an object from the list by its ID.
     */
    function remove($id)
    {
        $this->list_obs[$id] = null;
        unset($this->list_obs[$id]);
    }

    /**
     * Returns an object by its ID.
     */
    function get($id)
    {
        return $this->list_obs[$id];
    }
}

