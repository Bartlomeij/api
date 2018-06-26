<?php

namespace AppBundle\Entity;

use AppBundle\Traits\TimestampsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Serializer\ExclusionPolicy("all")
 * @ORM\Table(name="cart")
 * @ORM\Entity()
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "api_carts_show",
 *          parameters = { "id" = "expr(object.getId())" }
 *     )
 * )
 * @Hateoas\Relation(
 *     "user",
 *     href=@Hateoas\Route(
 *          "api_users_show",
 *          parameters = { "id" = "expr(object.getUser().getId())" }
 *     ),
 *     embedded="expr(object.getUser())"
 * )
 */
class Cart
{
    use TimestampsTrait {
        TimestampsTrait::__construct as private __TimestampsConstruct;
    }

    /**
     * @Serializer\Expose()
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @Serializer\Expose()
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(name="product_cart")
     */
    private $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->__TimestampsConstruct();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param Product $product
     */
    public function addProduct(Product $product)
    {
        if ($this->products->contains($product)){
            return;
        }

        if($this->products->count() >= 3){
            return;
        }

        $this->products[] = $product;
    }

    /**
     * @param Product $product
     */
    public function removeProduct(Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("totalPrice")
     * @return integer
     */
    public function getTotalPrice()
    {
        $total = 0;
        foreach($this->products as $product){
            $total += $product->getPrice();
        }
        return round($total, 2);
    }
}