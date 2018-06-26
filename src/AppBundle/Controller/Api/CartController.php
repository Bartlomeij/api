<?php

namespace AppBundle\Controller\Api;


use AppBundle\Api\ApiProblem;
use AppBundle\Controller\BaseController;
use AppBundle\Entity\Cart;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends BaseController
{
    /**
     * @Route("/api/carts", name="api_carts_new")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = new Cart();
        $cart->setUser($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($cart);
        $em->flush();

        $location = $this->generateUrl('api_carts_show', [
            'id' => $cart->getId()
        ]);

        $response = $this->createApiResponse($cart, 201);
        $response->headers->set('Location', $location);
        return $response;
    }

    /**
     * @Route("/api/carts/{id}", name="api_carts_show")
     * @Method("GET")
     */
    public function showAction($id)
    {
        $cart = $this->getDoctrine()
            ->getRepository('AppBundle:Cart')
            ->find($id);

        if (!$cart) {
            throw $this->createNotFoundException('No cart found for id '.$id);
        }

        if($cart->getUser()->getId() != $this->getUser()->getId()){
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $response = $this->createApiResponse($cart);
        return $response;
    }

    /**
     * @Route("/api/carts", name="api_carts_collection")
     * @Method("GET")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:Cart')
            ->createQueryBuilder('cart');

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $request, 'api_carts_collection');

        $response = $this->createApiResponse($paginatedCollection);
        return $response;
    }

    /**
     * @Route("/api/carts/{id}", name="api_carts_delete")
     * @Method("DELETE")
     * @param $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = $this->getDoctrine()
            ->getRepository('AppBundle:Cart')
            ->find($id);

        if ($cart) {
            if($cart->getUser()->getId() != $this->getUser()->getId()){
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($cart);
            $em->flush();
        }

        return new Response(null, 204);
    }

    /**
     * @Route("/api/carts/{cart_id}/products/{product_id}", name="api_carts_add_product")
     * @Method("PUT")
     * @param $cart_id
     * @param $product_id
     * @return Response
     */
    public function addProductToCartAction($cart_id, $product_id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = $this->getDoctrine()
            ->getRepository('AppBundle:Cart')
            ->find($cart_id);

        if (!$cart) {
            throw $this->createNotFoundException('No cart found for id '.$cart_id);
        }

        if($cart->getUser()->getId() != $this->getUser()->getId()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $product = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($product_id);

        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$product_id);
        }

        if($cart->getProducts()->count() >= 3) {
            $response = new JsonResponse(
                array(
                    'status' => 400,
                    'type' => 'about:blank',
                    'title' => "More products in cart are not acceptable"
                ),
                400
            );
            $response->headers->set('Content-Type', 'application/problem+json');
            return $response;
        }

        $cart->addProduct($product);

        $em = $this->getDoctrine()->getManager();
        $em->persist($cart);
        $em->flush();

        $response = $this->createApiResponse($cart);
        return $response;
    }

    /**
     * @Route("/api/carts/{cart_id}/products/{product_id}", name="api_carts_remove_product")
     * @Method("DELETE")
     * @param $cart_id
     * @param $product_id
     * @return Response
     */
    public function removeProductFromCartAction($cart_id, $product_id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = $this->getDoctrine()
            ->getRepository('AppBundle:Cart')
            ->find($cart_id);

        if (!$cart) {
            throw $this->createNotFoundException('No cart found for id '.$cart_id);
        }

        if($cart->getUser()->getId() != $this->getUser()->getId()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $product = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($product_id);

        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$product_id);
        }

        $cart->removeProduct($product);

        $em = $this->getDoctrine()->getManager();
        $em->persist($cart);
        $em->flush();

        $response = $this->createApiResponse($cart);
        return $response;
    }
}