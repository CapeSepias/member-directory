<?php

namespace App\Controller;

use App\Repository\MemberRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/directory/search")
 */
class SearchController extends AbstractController
{
    /**
     * @Route("/", name="search", options={"expose" = true})
     */
    public function index(MemberRepository $memberRepository, Request $request)
    {
        $results = [];
        if ($request->query->get('q')) {
            $results = $memberRepository->search($request->query->get('q'));
        }
        return $this->render('search/index.html.twig', [
            'results' => $results
        ]);
    }

    /**
     * @Route("/autocomplete", name="search_autocomplete", options={"expose" = true})
     */
    public function autoComplete(MemberRepository $memberRepository, Request $request)
    {
        $output = [];
        if ($request->query->get('q')) {
            $results = $memberRepository->search($request->query->get('q'), 10);
            foreach ($results as $member) {
                $output[] = [
                    'localIdentifier' => $member[0]->getLocalIdentifier(),
                    'displayName' => $member[0]->getDisplayName()
                ];
            }
        }
        return $this->json($output);
    }
}
