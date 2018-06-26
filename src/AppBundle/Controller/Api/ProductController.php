<?php

namespace AppBundle\Controller\Api;


use AppBundle\Controller\BaseController;
use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends BaseController
{
    /**
     * @Route("/api/products", name="api_products_new")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();

        $form = $this->createForm('AppBundle\Form\ProductForm', $product);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }
        $product->setUser($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        $location = $this->generateUrl('api_products_show', [
            'id' => $product->getId()
        ]);

        $response = $this->createApiResponse($product, 201);
        $response->headers->set('Location', $location);
        return $response;
    }

    /**
     * @Route("/api/products/{id}", name="api_products_show")
     * @Method("GET")
     */
    public function showAction($id)
    {
        $client = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($id);

        if (!$client) {
            throw $this->createNotFoundException('No product found for id '.$id);
        }

        $response = $this->createApiResponse($client);
        return $response;
    }
    /**
     * @Route("/api/products", name="api_products_collection")
     * @Method("GET")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $filter = $request->query->get('filter');

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->findAllQueryBuilder($filter);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $request, 'api_products_collection');

        $response = $this->createApiResponse($paginatedCollection);

        return $response;
    }


    /**
     * @Route("/api/products/{id}", name="api_products_update")
     * @Method({"PUT", "PATCH"})
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function updateAction($id, Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($id);

        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$id);
        }

        $form = $this->createForm('AppBundle\Form\ProductForm', $product);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        $response = $this->createApiResponse($product);
        return $response;
    }

    /**
     * @Route("/api/products/{id}", name="api_products_delete")
     * @Method("DELETE")
     * @param $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($id);

        if ($product) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();
        }

        return new Response(null, 204);
    }
}