<?php

namespace Hellonico\Fixtures\Entity;

interface EntityInterface
{

    /**
     * Set current entity ID
     * @param int $id
     */
    public function setCurrentId($id);

    /**
     * Check if entity
     * @param  int $id
     * @return boolean
     */
    public function exists($id);

    /**
     * Create object
     * @return int Database ID
     */
    public function create();

    /**
     * Persist object
     * @return boolean
     */
    public function persist();

    /**
     * Delete fixtures
     */
    public static function delete();
}
