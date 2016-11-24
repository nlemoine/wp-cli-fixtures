<?php

namespace Hellonico\Fixtures\Entity;

use ReflectionObject;
use ReflectionProperty;

abstract class Entity implements EntityInterface
{

    /**
     * Meta
     * @var array
     */
    public $meta;

    /**
     * Constructor
     * @param int $id
     */
    public function __construct($id = false)
    {
        if ($id && is_numeric($id) && $id > 0) {
            return $this->exists($id) ? $this->setCurrentId($id) : $this->setCurrentId(false);
        }
        $this->create();
    }

    /**
     * Get object data
     * @return array
     */
    protected function getData()
    {
        $data = get_object_vars($this);
        if (isset($this->extra)) {
            foreach ($this->extra as $extra) {
                if (!isset($data[$extra])) {
                    continue;
                }
                unset($data[$extra]);
            }
        }
        return array_filter($data);
    }

    /**
     * Get object metadata
     * @return array
     */
    protected function getMetaData()
    {
        // Handle meta_input in Post entity
        if (isset($this->meta_input) && is_array($this->meta_input) && !empty($this->meta_input)) {
            $this->meta = wp_parse_args($this->meta_input, $this->meta);
        }
        if ($this->meta && is_array($this->meta)) {
            return array_filter($this->meta);
        }
        return [];
    }

    /**
     * [filterProperties description]
     * @return boolean
     */
    protected function filterProperties()
    {
        // @todo check public entity proerties
        $public_properties = array_column((new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC), 'name');
    }
}
