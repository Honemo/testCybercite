<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\CrawlService;
use App\Service\DataGardenService;
use Symfony\Component\Validator\Constraints as Assert;

class CrawlController extends AbstractController
{
    
    /**
     * @Route("/", name="app_crawl")
     */
    public function index(CrawlService $crawl): Response
    {
        $crawl->getAllExportedFiles();
        return $this->render('base.html.twig', [
            'exportedCrawls' => $crawl->getAllExportedFiles(),
            'postForm' => $this->generateUrl('post_crawl'),
        ]);
    }

    /**
     * @Route("/crawl", name="post_crawl", methods={"POST"})
     */
    public function crawl(Request $request, CrawlService $crawl, DataGardenService $datagarden): RedirectResponse
    {
        $postData = $request->request->all();
        // Crawl le site
        $crawl->startIt($postData['url']);
        // Récupère toutes les positions du site indexées par url
        $positionsByUrls = $datagarden->getAllLastsPositionsByUrl();
        // Met à jour la bdd en fonction des positions
        $crawl->calculateUrlPositionsCorrespondance($positionsByUrls);
        // Exporte le crawl dans son fichier
        $crawl->export();
        // On retourne sur la HP
        $homePage = $this->generateUrl('app_crawl');
        return new RedirectResponse($homePage, 301);
    }
}
