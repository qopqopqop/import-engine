<?php
namespace Mathielen\ImportEngine\ValueObject;

class StorageSelection
{

    protected $providerId = 'defaultProvider';
    protected $id;
    protected $name;
    protected $impl;

    public function __construct($impl, $id=null, $name=null, $providerId = 'defaultProvider')
    {
        $this->impl = $impl;
        $this->id = $id;
        $this->name = $name;
        $this->providerId = $providerId;
    }

    public function getProviderId()
    {
        return $this->providerId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getImpl()
    {
        //TODO some sort of serializable wrapper?
        if (is_array($this->impl)) {
            $reflectionClass = new \ReflectionClass($this->impl['class']);

            return $reflectionClass->newInstanceArgs($this->impl['args']);
        }

        return $this->impl;
    }

    public function prePersist()
    {
        if ($this->impl instanceof \SplFileInfo) {

            //TODO some sort of serializable wrapper?
            $this->impl = array(
                'class'=>get_class($this->impl),
                'args'=>array($this->impl->getRealPath())
            );
        }
    }

}