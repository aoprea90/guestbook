<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    private $twig;

    private $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/conference", name="homepage")
     */
    public function index(): Response
    {
        return new Response($this->twig->render('conference/index.html.twig', []));
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     * @param CommentRepository $commentRepository
     * @param Conference $conference
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function show(Request $request, CommentRepository $commentRepository, Conference $conference, string $photoDir) : Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photoFilename']->getData()) {
                $fileName = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();

                try{
                    $photo->move($photoDir, $fileName);
                } catch (FileException $e) {
                    // unable to upload the photo, give up
                }

                $comment->setPhotoFilename($fileName);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0,$request->query->getInt('offset', 0));
        $paginator = $commentRepository->getConferenceComments($conference, $offset);


        return new Response($this->twig->render('conference/show.html.twig', [
             'conference' => $conference,
             'comments' => $paginator,
             'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
             'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
             'comment_form' => $form->createView()
        ]));
    }

}
