<?php

namespace App\Service;

use App\Repository\UrlRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Entity\Url;
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

    public function __construct(UrlRepository $urlRepository)
    {
        $this->urlRepository = $urlRepository;
        $this->httpClient = new Client();
    }

    /**
     * Crawle une url et la stocke dans la bdd avec son status
     * Si $recursif est à true crawl les liens internes présents sur la page
     *
     * @param string $url
     * @param boolean $recursif
     * @return void
     */
    public function crawlUrl(string $url, bool $recursif = true) {
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
}
