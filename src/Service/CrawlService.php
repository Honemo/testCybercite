<?php

namespace App\Service;

use App\Repository\UrlRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Entity\Url;
use DateTime;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Exception;

class CrawlService
{
    /**
     * The url repository
     *
     * @var UrlRepository
     */
    private $urlRepository;

    /**
     * A http client
     *
     * @var Client
     */
    private $httpClient;

    /**
     * Le kernel pour la manipulation des paths
     *
     * @var KernelInterface
     */
    private $kernel;

    static $exportTemplateFileName = "tpl.html";

    public function __construct(UrlRepository $urlRepository, KernelInterface $kernel)
    {
        $this->urlRepository = $urlRepository;
        $this->httpClient = new Client();
        $this->kernel = $kernel;
    }

    /**
     * Lande le crawl de l'url de base passée en paramètre
     *
     * @param string $url
     * @return void
     */
    public function startIt(string $url) {
        $this->urlRepository->truncate();
        $this->crawlUrl($url);
    }

    /**
     * Crawle une url et la stocke dans la bdd avec son status
     * Si $recursif est à true crawl les liens internes présents sur la page
     *
     * @param string $url
     * @param boolean $recursif
     * @return void
     */
    protected function crawlUrl(string $url, bool $recursif = true) {
        try {
            // On crawl l'url passée en paramètre
            $response = $this->httpClient->request('GET', $url);
            $statusCode = $response->getStatusCode();
            $uneUrlCrawlee = new Url($url, (string) $statusCode);
            // Save l'url et son status dans la bdd
            $this->urlRepository->add($uneUrlCrawlee, true);
            // On récupère la liste des liens internes présents dans la réponse si la page est en 200
            if ($statusCode === 200) {
                /**@todo Le mettre dans un autre service */
                $parsedUrl = parse_url($url);
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true); // Supprimez les erreurs liées au HTML mal formé
                $dom->loadHTML($response->getBody()->getContents());
                libxml_clear_errors();
                $xpath = new \DOMXPath($dom);
                $links = $xpath->query("//a[@href]");
                $urls = [];
                foreach ($links as $link) {
                    $linkUrl = $link->getAttribute('href');
                    $parsedLinkUrl = parse_url($linkUrl);
                    if (isset($parsedLinkUrl['host']) && ($parsedLinkUrl['host'] === $parsedUrl['host'])) {
                        // Ajoutez l'URL à votre tableau d'URLs
                        $urls[] = $linkUrl;
                    }

                }
                // Remove Dupplicates
                $internalLinks = array_unique($urls);
                /**@endtodo */
                if ($recursif === true) {
                    // C'est parti pour un crawl recursif \o/
                    foreach ($internalLinks as $anInternalLink) {
                        if ($this->urlRepository->alreadyExists($anInternalLink) === false) {
                            $this->crawlUrl($anInternalLink, false);
                        }
                    }
                }
            }
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
            } else {
                $statusCode = "0";
            }
            $uneUrlCrawlee = new Url($url, (string) $statusCode);
            // Save l'url et son status dans la bdd
            $this->urlRepository->add($uneUrlCrawlee, true);
        }
    }

    /**
     * Met à jour les entitées Url avec leurs positions
     *
     * @param array $lastPositionsByUrls
     * @return void
     */
    public function calculateUrlPositionsCorrespondance(array $lastPositionsByUrls)
    {
        foreach($lastPositionsByUrls as $aPositionUrl => $aPosition) {
            if (!$anUrl = $this->urlRepository->findOneBySLug($aPositionUrl)) {
                $anUrl =new Url($aPositionUrl,"666");
                
            }
            $anUrl->setPositions($aPosition);
            $this->urlRepository->add($anUrl);
        }
    }

    /**
     * Exporte le crawl courant dans un fichier html 
     *
     * @return boolean
     */
    public function export():bool
    {
        $filesystem = new Filesystem();
        $htmlContent = "";
        foreach ($this->urlRepository->findAll() as $anUrl) {
            $htmlContent.= $anUrl->exportAsHtmlTableRow();
        }
        $tpl = $this->getExportTemplate();
        $fileContent = sprintf($tpl, $htmlContent);
        try {
            $filesystem->appendToFile('exports/'.$this->getExportFileName(), $fileContent);
            return true;
        } catch (Exception $exp) {
            return false;
        }
    }

    /**
     * Retourne le contenu du template d'export html
     *
     * @return string
     */
    protected function getExportTemplate():string
    {
        // Instanciation du composant FileSystem
        $filesystem = new Filesystem();

        // Vérification si le fichier existe
        if ($filesystem->exists(self::$exportTemplateFileName)) {
            try {
                // Instanciation de la classe File pour lire le contenu
                $file = new File(self::$exportTemplateFileName);
                // Lecture du contenu du fichier
                $contenu = $file->getContent();
            } catch (FileNotFoundException $e) {
                $contenu = "Woooops";
            }
        } else {
            $contenu = 'Le fichier n\'existe pas.';
        }
        return $contenu;
    }

    /**
     * Retourne le nom du fichier qui sera utilisé pour l'export
     *
     * @return string
     */
    protected function getExportFileName():string
    {
        return (new DateTime())->format("Ymdhms").".html";
    }

    /**
     * Retourne tous les fichiers de crawl qui ont été exportés
     *
     * @return array
     */
    public function getAllExportedFiles():array
    {
        $exportedCrawlList = [];
        $publicDirectory = $this->kernel->getProjectDir() . '/public/exports';
        /// Instanciation de Finder
        $finder = new Finder();
        // Recherche de tous les fichiers dans le répertoire
        $fichiers = $finder->files()->in($publicDirectory);
        foreach ($fichiers as $fichier) {
            $exportedCrawlList[] = [
                'filename'=>$fichier->getFilename(),
                'alt' => ''
            ];
        }
        return $exportedCrawlList;
    }
}
