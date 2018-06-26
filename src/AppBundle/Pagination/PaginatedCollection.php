<?php

namespace AppBundle\Pagination;


class PaginatedCollection
{
    private $items;
    private $total;
    private $count;

    private $_links = array();

    /**
     * PaginatedCollection constructor.
     * @param $items
     * @param $total
     */
    public function __construct($items, $total)
    {
        $this->items = $items;
        $this->total = $total;
        $this->count = count($items);
    }

    public function addLink($ref, $url)
    {
        $this->_links[$ref] = $url;
    }
}