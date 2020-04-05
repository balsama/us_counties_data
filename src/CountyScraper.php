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
        foreach($this->states as $state) {
            $data = $this->scrape($state);
            $this->counties[$state] = $data;
        }
    }

    private function scrape($state) {
        $counties = [];
        $rows = $this->getRows($state);
        foreach ($rows as $row) {
            if (is_object($row->getElementsByTagName('th')->item(1))) {
                // @todo this checks to see ift he second cell is a th and asumes it's the heading row. This could be
                //    strengthened.
                $headings  = $row->getElementsByTagName('th');
                $areaColumn = $this->findColumns($headings, 'Area');
                $populationColumn = $this->findColumns($headings, 'Pop');
            }
            if (is_object($row->getElementsByTagName('td')->item(6))) {
                // @todo Assuming that if the row has at least 7 cells, it holds a county. A better way would be
                //   to check the first cell (a th) has the word county in it.
                $countyName = $this->removeNewLines($row->getElementsByTagName('th')->item(0)->nodeValue);
                $population = $this->formatPopulation($row->getElementsByTagName('td')->item($populationColumn)->nodeValue);
                $area = $this->extractSqareMilesFromArea($row->getElementsByTagName('td')->item($areaColumn)->nodeValue);
                $density = (int) number_format($population / $area, 0, '.', '');
                $counties["$countyName, $state"] = [
                    'name' => $countyName,
                    'state' => $state,
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
        "Alabama",
        "Alaska",
        "Arizona",
        "Arkansas",
        "California",
        "Colorado",
        "Connecticut",
        "Delaware",
        "Florida",
        "Georgia",
        "Hawaii",
        "Idaho",
        "Illinois",
        "Indiana",
        "Iowa",
        "Kansas",
        "Kentucky",
        "Louisiana",
        "Maine",
        "Montana",
        "Nebraska",
        "Nevada",
        "New Hampshire",
        "New Jersey",
        "New Mexico",
        "New York",
        "North Carolina",
        "North Dakota",
        "Ohio",
        "Oklahoma",
        "Oregon",
        "Maryland",
        "Massachusetts",
        "Michigan",
        "Minnesota",
        "Mississippi",
        "Missouri",
        "Pennsylvania",
        "Rhode Island",
        "South Carolina",
        "South Dakota",
        "Tennessee",
        "Texas",
        "Utah",
        "Vermont",
        "Virginia",
        "Washington",
        "West Virginia",
        "Wisconsin",
        "Wyoming",
    ];

}