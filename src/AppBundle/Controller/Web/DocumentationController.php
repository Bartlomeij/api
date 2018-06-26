<?php

namespace AppBundle\Controller\Web;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DocumentationController extends Controller
{
    /**
     * @Route("/documentation", name="documentation")
     * @Method("GET")
     */
    public function documentationAction()
    {
        $baseUrl = 'http://localhost:8000/api';
        return $this->render('documentation.html.twig', [
            'baseUrl' => $baseUrl
        ]);
    }
}