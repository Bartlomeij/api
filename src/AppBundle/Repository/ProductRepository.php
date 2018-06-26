<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Product;

class ProductRepository extends EntityRepository
{
    /**
     * @param $name
     * @return Product
     */
    public function findOneByName($name)
    {
        return $this->findOneBy(array('name' => $name));
    }


    /**
     * @param $limit
     * @return Product[]
     */
    public function findRandom($limit)
    {
        $products = $this->createQueryBuilder('p')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute()
        ;

        shuffle($products);

        return array_slice($products, 0, $limit);
    }

    /**
     * @param string $filter
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllQueryBuilder($filter = '')
    {
        $qb = $this->createQueryBuilder('product');

        if($filter) {
            $qb->andWhere('product.title LIKE :filter')
                ->setParameter('filter', '%'.$filter.'%');
        }

        return $qb;
    }
}
