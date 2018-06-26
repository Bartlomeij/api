<?php


namespace AppBundle\Entity;


use AppBundle\Traits\TimestampsTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Serializer\ExclusionPolicy("all")
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @UniqueEntity(fields={"username"}, message="This username is already taken")
 * @UniqueEntity(fields={"email"}, message="This email address is already taken")
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "api_users_show",
 *          parameters = { "id" = "expr(object.getId())" }
 *     )
 * )
 */
class User implements UserInterface
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
     * @ORM\Column(type="string", unique=true, length=255)
     * @Assert\NotBlank(message="Please enter a proper username")
     */
    private $username;

    /**
     * @Serializer\Expose()
     * @Assert\Email ()
     * @ORM\Column(type="string", unique=true, length=255)
     * @Assert\NotBlank(message="Please enter a proper email")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"Registration"}, message="Please enter a proper password")
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = [];

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }
    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getRoles()
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles)){
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {

    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        // guarantees that the entity looks "dirty" to Doctrine
        // when changing the plainPassword
        $this->password = null;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function getEmail()
    {
        return $this->email;
    }

}