<?php

namespace Balsama;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

class CountyScraper
{
    private ClientInterface $client;
    private string $url = 'https://en.wikipedia.org/wiki/List_of_counties_in_';
    private array $counties = [];

    public function __construct() {
        libxml_use_internal_errors(true);
        $this->setupTools();
        $this->scrapeCountyData();
    }
    
    public function getCounties() {
        return $this->counties;
    }

    private function scrapeCountyData() {
        foreach($this->states as $stateFipsCode => $state) {
            $data = $this->scrape($state, $stateFipsCode);
            $this->counties[$state] = $data;
        }
    }

    private function scrape($state, $statFipsCode) {
        $counties = [];
        $rows = $this->getRows($state);
        foreach ($rows as $row) {
            if (is_object($row->getElementsByTagName('th')->item(1))) {
                // @todo this checks to see ift he second cell is a th and asumes it's the heading row. This could be
                //    strengthened.
                $headings  = $row->getElementsByTagName('th');
                $areaColumn = $this->findColumns($headings, 'Area');
                $populationColumn = $this->findColumns($headings, 'Pop');
                $fipsColumn = $this->findColumns($headings, 'FIPS');
            }
            if (is_object($row->getElementsByTagName('td')->item(6))) {
                // @todo Assuming that if the row has at least 7 cells, it holds a county. A better way would be
                //   to check the first cell (a th) has the word county in it.
                $countyName = $this->removeNewLines($row->getElementsByTagName('th')->item(0)->nodeValue);
                $fipsCode = (string) $statFipsCode . $this->removeNewLines($row->getElementsByTagName('td')->item($fipsColumn)->nodeValue);
                $population = $this->formatPopulation($row->getElementsByTagName('td')->item($populationColumn)->nodeValue);
                if (!isset($areaColumn)) {
                    $areaColumn = 0;
                }
                $area = $this->extractSqareMilesFromArea($row->getElementsByTagName('td')->item($areaColumn)->nodeValue);
                $density = (int) number_format($population / $area, 0, '.', '');
                $counties["$countyName, $state"] = [
                    'name' => $countyName,
                    'state' => $state,
                    'fips' => $fipsCode,
                    'population' => $population,
                    'area' => $area,
                    'density' => $density,
                ];
            }
        }
        return $counties;
    }

    private function getRows($state) {
        $html = $this->fetch($this->url . $state);
        $dom = new \domDocument;
        $dom->loadHTML($html);
        $dom->preserveWhiteSpace = false;
        $finder = new \DomXPath($dom);
        $classname = "wikitable";
        $table = $finder->query("//*[contains(@class, '$classname')]");
        $table = $table->item(0);
        $rows = $table->getElementsByTagName('tr');

        return $rows;
    }

    private function findColumns($headingsRow, $searchTerm) {
        $i = 0;
        foreach ($headingsRow as $heading) {
            if (strpos($heading->nodeValue, $searchTerm) !== false) {
                // Find out which column contains the area info.
                return ($i - 1);
            }
            $i++;
        }
    }

    private function extractSqareMilesFromArea($area) {
        $sqm = strtok($area, 'sq');
        $sqm = str_replace(',', '', $sqm);
        return (int) $sqm;
    }

    private function formatPopulation($population) {
        $population = $this->removeNewLines($population);
        $population = str_replace(',', '', $population);
        return (int) $population;
    }

    private function removeNewLines($string) {
        return trim(str_replace("\n", "", $string));
    }

    private function fetch($url, $retryOnError = true) {
        try {
            /** @var $response ResponseInterface $response */
            $response = $this->client->get($url);
            $body = (string) $response->getBody();
            return $body;
        }
        catch (ServerException $e) {
            if ($retryOnError) {
                return $this->getCurrentData();
            }
            echo 'Caught response: ' . $e->getResponse()->getStatusCode();
        }
    }

    private function setupTools() {
        $this->client = new Client();
    }

    private $states = [
        "01" => "Alabama",
        "02" => "Alaska",
        "04" => "Arizona",
        "05" => "Arkansas",
        "06" => "California",
        "08" => "Colorado",
        "09" => "Connecticut",
        "10" => "Delaware",
        "12" => "Florida",
        "13" => "Georgia",
        "15" => "Hawaii",
        "16" => "Idaho",
        "17" => "Illinois",
        "18" => "Indiana",
        "19" => "Iowa",
        "20" => "Kansas",
        "21" => "Kentucky",
        "22" => "Louisiana",
        "23" => "Maine",
        "24" => "Maryland",
        "25" => "Massachusetts",
        "26" => "Michigan",
        "27" => "Minnesota",
        "28" => "Mississippi",
        "29" => "Missouri",
        "30" => "Montana",
        "31" => "Nebraska",
        "32" => "Nevada",
        "33" => "New Hampshire",
        "34" => "New Jersey",
        "35" => "New Mexico",
        "36" => "New York",
        "37" => "North Carolina",
        "38" => "North Dakota",
        "39" => "Ohio",
        "40" => "Oklahoma",
        "41" => "Oregon",
        "42" => "Pennsylvania",
        "44" => "Rhode Island",
        "45" => "South Carolina",
        "46" => "South Dakota",
        "47" => "Tennessee",
        "48" => "Texas",
        "49" => "Utah",
        "50" => "Vermont",
        "51" => "Virginia",
        "53" => "Washington",
        "54" => "West Virginia",
        "55" => "Wisconsin",
        "56" => "Wyoming",
    ];

}