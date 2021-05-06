<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/", name="post_index", methods={"GET"})
     */
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // on recupre les images
            $images = $form->get('images')->getData();

            // on boucle sur les images
            foreach($images as $image) {
                // on recupere un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // on copie le fichie dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                // Ons stock l'image dans la base de donnée
                $img = new Image();
                $img->setName($fichier);

                $post->addImage($img);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"})
     */
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // on recupre les images
            $images = $form->get('images')->getData();

            // on boucle sur les images
            foreach($images as $image) {
                // on recupere un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // on copie le fichie dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                // Ons stock l'image dans la base de donnée
                $img = new Image();
                $img->setName($fichier);

                $post->addImage($img);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"POST"})
     */
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * @Route("/delete/image/{id}", name="post_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Image $image, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // On verifie si le token est valide
        // On recupere le nom de l'image
        // On supprime l'image
        if ($this->isCsrfTokenValid('delete', $data['_token'])) {
            $name = $image->getName();

            unlink($this->getParameter('images_directory') . '/' . $name);

            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            // On repond en JSON
            return new JsonResponse(['success' => 1]); 

        } else {
            return new JsonResponse(['error' => 'Token invalid'], 400);
        }
    }
}
