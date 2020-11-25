<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @Route("/conference", name="homepage")
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
    }

    /**
     * @Route("/conference/{id}", name="conference")
     * @param CommentRepository $commentRepository
     * @param Conference $conference
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function show(Request $request, CommentRepository $commentRepository, Conference $conference) : Response
    {
        $offset = max(0,$request->query->getInt('offset', 0));
        $paginator = $commentRepository->getConferenceComments($conference, $offset);


        return new Response($this->twig->render('conference/show.html.twig', [
             'conference' => $conference,
             'comments' => $paginator,
             'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
             'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
        ]));
    }

}
