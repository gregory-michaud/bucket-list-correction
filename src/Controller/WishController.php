<?php

namespace App\Controller;

use App\Entity\Wish;
use App\Form\WishType;
use App\Repository\WishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wish/', name: 'wish_')]
class WishController extends AbstractController
{
    #[Route('list', name: 'list')]
    public function list(WishRepository $wishRepository): Response
    {
        $wishes = $wishRepository->findWishesWithCategory();


        return $this->render('wish/list.html.twig', [
            'wishes' => $wishes
        ]);
    }

    #[Route('detail/{id}', name: 'detail')]
    public function detail(int $id, WishRepository $wishRepository): Response
    {

        $wish = $wishRepository->find($id);

        return $this->render('wish/detail.html.twig', [
            'wish' => $wish
        ]);
    }

    #[Route('create', name: 'create')]
    #[IsGranted("ROLE_USER")]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $wish = new Wish();

        $wishForm = $this->createForm(WishType::class, $wish);

        // hydrade l'instance wish avec les données de la request
        $wishForm->handleRequest($request);
        dump($wish);

        if($wishForm->isSubmitted() && $wishForm->isValid()){
            // renseigne les infos qui ne sont pas dans le formulaire
            $wish->setDateCreated(new \DateTime());
            $wish->setIsPublished(true);
            // valorise l'auteur du souhait avec le user connecté
            $wish->setAuthor($this->getUser()->getUserIdentifier());

            // enregistre le souhait en BDD
            $entityManager->persist($wish);
            $entityManager->flush();

            $this->addFlash('success', 'Idea successfully added!');

            return $this->redirectToRoute('wish_detail', ['id' => $wish->getId()]);

        }

        return $this->render('wish/create.html.twig', [
            'wishForm' => $wishForm
        ]);
    }

    #[Route('delete/{id}', name: 'delete')]
    public function delete(int $id,
                           WishRepository $wishRepository,
                           EntityManagerInterface $entityManager,
                            Request $request): Response
    {
        if($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))){
            //rechercher le souhait à supprimer
            $wishDelete = $wishRepository->find($id);

            $entityManager->remove($wishDelete);
            $entityManager->flush();
        }


        return $this->redirectToRoute('wish_list');
    }


    #[Route('demo', name: 'demo')]
    public function demo(EntityManagerInterface $entityManager){

        $wish = new Wish();
        $wish->setTitle('Faire le tour du monde');
        $wish->setAuthor('moi');
        $wish->setDescription('en bateau');
        $wish->setDateCreated(new \DateTime());
        $wish->setIsPublished(true);

        $entityManager->persist($wish);
        $entityManager->flush();

        return $this->render('wish/list.html.twig');

    }



}
