<?php

namespace Hellonico\Fixtures\Entity;

abstract class Entity implements EntityInterface
{
    /**
     * Meta.
     *
     * @var array
     */
    public $meta;

    /**
     * Constructor.
     *
     * @param int $id
     */
    public function __construct($id = false)
    {
        if ($id && absint($id) > 0) {
            $post = get_post($id);
            foreach ($post as $field => $value) {
                $this->{$field} = $value;
            }

            return $this->exists($id) ? $this->setCurrentId($id) : $this->setCurrentId(false);
        }
        $this->create();
    }

    /**
     * Get object data.
     *
     * @return array
     */
    protected function getData()
    {
        // Convert DateTime objects to string
        foreach ($this as $key => $value) {
            if ($value instanceof \DateTime) {
                $this->{$key} = $value->format('Y-m-d H:i:s');
            }

            if (is_array($value)) {
                array_walk_recursive($value, function (&$v, $k) {
                    if ($v instanceof \DateTime) {
                        $v = $v->format('Y-m-d H:i:s');
                    }
                });
                $this->{$key} = $value;
            }
        }

        // Convert object properties to array and remove extra fields
        $data = get_object_vars($this);
        if (isset($this->extra)) {
            foreach ($this->extra as $extra) {
                if (!isset($data[$extra])) {
                    continue;
                }
                unset($data[$extra]);
            }
        }

        return $this->filterData($data);
    }

    /**
     * Get object metadata.
     *
     * @return array
     */
    protected function getMetaData()
    {
        // Handle meta that can be passed in Comment and Post entities
        $merge_attributes = ['meta_input', 'comment_meta'];
        foreach ($merge_attributes as $attribute) {
            if (isset($this->{$attribute}) && is_array($this->{$attribute}) && !empty($this->{$attribute})) {
                $this->meta = wp_parse_args($this->{$attribute}, $this->meta);
            }
        }
        if ($this->meta && is_array($this->meta)) {
            return $this->filterData($this->meta);
        }

        return [];
    }

    /**
     * [filterProperties description].
     *
     * @return bool
     */
    protected function filterProperties()
    {
        // @todo check public entity properties
        $public_properties = array_column((new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC), 'name');
    }

    /**
     * Removes null values from array.
     *
     * @param array $array
     *
     * @return array
     */
    private function filterData($array)
    {
        return array_filter($array, function ($v) {
            return !is_null($v);
        });
    }
}
