<?php

namespace AppBundle\Traits;

use JMS\Serializer\Annotation as Serializer;

trait TimestampsTrait
{

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated_at;

    public function __construct()
    {
        if(empty($this->created_at))
            $this->setCreatedAt();

        $this->setUpdatedAt();
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($created_at = null)
    {
        if($created_at == null)
            $created_at = new \DateTime();

        $this->created_at = $created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt($updated_at = null)
    {
        if($updated_at == null)
            $updated_at = new \DateTime();

        $this->updated_at = $updated_at;
    }
}