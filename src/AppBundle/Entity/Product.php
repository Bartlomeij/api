<?php

namespace AppBundle\Entity;

use AppBundle\Traits\TimestampsTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Serializer\ExclusionPolicy("all")
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "api_products_show",
 *          parameters = { "id" = "expr(object.getId())" }
 *     )
 * )
 */
class Product
{
    use TimestampsTrait;

    /**
     * @Serializer\Expose()
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Serializer\Expose()
     * @ORM\Column(type="string", nullable=false, length=255)
     * @Assert\NotBlank(message="Please enter a proper title")
     */
    private $title;

    /**
     * @Serializer\Expose()
     * @ORM\Column(type="float", nullable=false, length=255)
     * @Assert\NotBlank(message="Please enter a proper price")
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }


}