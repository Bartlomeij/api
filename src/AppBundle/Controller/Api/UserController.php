<?php

namespace AppBundle\Controller\Api;


use AppBundle\Controller\BaseController;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseController
{
    /**
     * @Route("/api/users", name="api_users_new")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm('AppBundle\Form\UserForm', $user);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $location = $this->generateUrl('api_users_show', [
            'id' => $user->getId()
        ]);

        $response = $this->createApiResponse($user, 201);
        $response->headers->set('Location', $location);
        return $response;
    }

    /**
     * @Route("/api/users/{id}", name="api_users_show")
     * @Method("GET")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($id);


        if($id != $this->getUser()->getId()){
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        if (!$user) {
            throw $this->createNotFoundException('No user found for id '.$id);
        }

        $response = $this->createApiResponse($user);
        return $response;
    }

    /**
     * @Route("/api/users", name="api_users_collection")
     * @Method("GET")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filter = $request->query->get('filter');

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findAllQueryBuilder($filter);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $request, 'api_users_collection');

        $response = $this->createApiResponse($paginatedCollection);

        return $response;
    }

    /**
     * @Route("/api/users/{id}", name="api_users_update")
     * @Method({"PUT", "PATCH"})
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function updateAction($id, Request $request)
    {
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($id);

        if($id != $this->getUser()->getId()){
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        if (!$user) {
            throw $this->createNotFoundException('No user found for id '.$id);
        }


        $form = $this->createForm('AppBundle\Form\UpdateUserForm', $user);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $response = $this->createApiResponse($user);
        return $response;
    }

    /**
     * @Route("/api/users/{id}", name="api_users_delete")
     * @Method("DELETE")
     * @param $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($id);

        if($id != $this->getUser()->getId()){
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        if ($user) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return new Response(null, 204);
    }
}