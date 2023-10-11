<?php

namespace App\Service;

use CyberCite\DataGardenApiClient\Client;
use DateTime;

class DataGardenService
{
    /**
     * Un client pour l'api de datagarden
     *
     * @var Client
     */
    protected $client;

    /**
     * L'id du site paramétré dans le fichier env
     *
     * @var string
     */
    protected $siteId;

    /**
     * L'id du rapport correspondant au site
     *
     * @var string
     */
    protected $racId;

    /**
     * La date du dernier rapport
     *
     * @var [type]
     */
    protected $lastRacDate;

    /**
     * L'id du moteur de recherche par défaut du rapport
     *
     * @var string
     */
    protected $motorId;

    /**
     * La liste de kw paramétrés sur le rapport de positionnement
     * idKw => labelDuKw
     *
     * @var array
     */
    protected $kws = null;

    public function __construct(string $datagardenApiLogin, string $datagardenApiPassword, string $datagardenApiIdSite, string $datagardenApiUrl)
    {
        $this->client = new Client($datagardenApiLogin, $datagardenApiPassword, $datagardenApiUrl);
        $this->client->login();
        $this->siteId = $datagardenApiIdSite;
        $siteInfos = $this->client->getSiteInfos($this->siteId);
        $this->racId = $siteInfos['rapports'][0]['id'];
        $this->motorId = $siteInfos['rapports'][0]['moteurs'][0]['id'];
        $this->lastRacDate = (new DateTime($siteInfos['rapports'][0]['last_date']))->format("Y-m-d");
    }

    /**
     * Retourne la liste des kws paramétrés pour le rapport du site
     *
     * @return array
     */
    public function getKws():array
    {
        if ($this->kws === null) {
            $aSite = $this->client->getSiteInfos($this->siteId );
            $racId = $aSite['rapports'][0]['id'];
            $kws = $this->client->getRapportExpressionsLiees($racId);
            foreach($kws as $kw)
            {
                if (!isset($this->kws[$kw['expression']['id']])) {
                    $this->kws[$kw['expression']['id']] = $kw['expression']['nom'];
                }
            }
        }
        return $this->kws;
    }

    /**
     * Récupère les résultats de positions indexés par URLS
     *
     * @return array
     */
    public function getAllLastsPositionsByUrl():array
    {
        $positionsByUrls = [];
        // Pour chaque kw paramétré sur le rapport
        foreach ($this->getKws() as $idKW => $labelKw){
            $paramsAppels = [
                "outId" => $this->motorId,
                "expressionId" => $idKW,
                "date" => $this->lastRacDate,
            ];
            // Récupère les résultats de positions pour le KW
            $rslts = $this->client->getRapportPositionsResultats($this->racId, $paramsAppels);
            foreach($rslts as $aRslt) {
                $positionsByUrls[$aRslt['url']][] = [
                    "expression" => $aRslt['expression']['nom'],
                    "position" => $aRslt['position'],
                ];
            }
        }
        return $positionsByUrls;
    }
}
