<?php

namespace App\Http\Controllers;

use DOMDocument;
use DOMXPath;

class UtilityController extends Controller
{
    /**
     * Get formatted url 
     *
     * @param  string $url
     * @return string
     */
    public static function formatUrl($url): string
    {
        return substr($url, -1) === '/' 
                                ? rtrim($url, '/') 
                                : $url;        
    }
    
    /**
     * getRandomWebLinks
     *
     * @param  mixed $anchors
     * @param  int $count
     * @param  string $url
     * @return array
     */
    public static function getRandomWebLinks($anchors, $count, $url): array
    {
        $allWebLinks = [];
        foreach($anchors as $element) {
            $href = $element->getAttribute('href');
            
            // Exclude external links
            if (parse_url($href, PHP_URL_HOST) !== null) {
              continue;
            }
            
            // Exclude links with # and #main
            if ($href === '#' || $href === '#main') {
              continue;
            }

            $allWebLinks[] = $href;
        }

        // Remove duplicate links
        array_unique($allWebLinks);

        // Shuffle array to fetch random links.
        shuffle($allWebLinks);

        // Fetch links as per the count
        $randomLinks = array_slice($allWebLinks , 0, $count);

        // Attach domain name to selected links
        $selectedLinks = preg_filter('/^/', $url, $randomLinks);   

        return $selectedLinks;
    }
    
    /**
     * getProcessedData
     *
     * @param  array $array
     * @return array
     */
    public static function getProcessedData($array): array
    {
        $processedLinks = [];

        for($i=0; $i < count($array); $i++) {

            $linkValues = UtilityController::getHttpValues($array[$i]);
            $linkHtmlValues = UtilityController::getHtmlValues($array[$i]);
            
            $processedLinks[$i]['url'] = $array[$i];
            $processedLinks[$i]['load_time'] = $linkValues['load_time'];
            $processedLinks[$i]['http_status_code'] = $linkValues['http_code'];
            $processedLinks[$i]['img_count'] = $linkHtmlValues['img_count'];
            $processedLinks[$i]['int_link_count'] = $linkHtmlValues['int_link_count'];
            $processedLinks[$i]['ext_link_count'] = $linkHtmlValues['ext_link_count'];
            $processedLinks[$i]['page_title_length'] = $linkHtmlValues['page_title_length'];
            $processedLinks[$i]['page_word_count'] = UtilityController::getWordCount($array[$i]);
        }

        return $processedLinks;
    }
    
    /**
     * getHttpValues
     *
     * @param  string $url
     * @return array
     */
    public static function getHttpValues($url): array
    {
        // set start time before page load
        $starttime = microtime(true);

        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $resource = curl_exec($handle);
        // get HTTP status code
        $values['http_code'] = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        
        // set end time after page load
        $endtime = microtime(true);

        // calculate page load time
        $values['load_time'] = round(($endtime - $starttime), 3);

        return $values;         
    }
    
    /**
     * getWordCount
     *
     * @param  string $url
     * @return int
     */
    public static function getWordCount($url): int
    {
        $str = file_get_contents($url);
        // Omit everything inside style and script tags
        $str = preg_replace('/<style\\b[^>]*>(.*?)<\\/style>/s', '', $str);
        $str = preg_replace('/<script\\b[^>]*>(.*?)<\\/script>/s', '', $str);
        $str = strip_tags(strtolower($str));
        $words = str_word_count($str, 1);

        return count($words);
    }
    
    /**
     * getHtmlValues
     *
     * @param  string $url
     * @return array
     */
    public static function getHtmlValues($url): array
    {
        $webImages = [];
        $internalLinks = [];
        $externalLinks = [];

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($url);

        // Get images count
        $images = $dom->getElementsByTagName('img');

        foreach($images as $img) {
            $webImages[] = $img->getAttribute('src');
        }

        // Get internal and external links
        $anchors = $dom->getElementsByTagName('a');

        foreach($anchors as $element) {
            $href = $element->getAttribute('href');
            if ($href === '#' || $href === '#main') {
              continue;
            }
            
            parse_url($href, PHP_URL_HOST) === null 
                                               ? $internalLinks[] = $href
                                               : $externalLinks[] = $href;
        }

        // Get page title length
        $xpath = new DOMXPath($dom);
        
        // Get a DOMNodeList containing all nodes
        $pageTitle =  $xpath->query('//title')->item(0)->nodeValue;

        $values['page_title_length'] = strlen($pageTitle);
        $values['img_count'] = count(array_unique($webImages));
        $values['int_link_count'] = count(array_unique($internalLinks));
        $values['ext_link_count'] = count(array_unique($externalLinks));

        return $values;
    }
   
    /**
     * Returns formatted data for Datatables 
     *
     * @param  array $array
     * @return array
     */
    public static function getFormattedData($array): array
    {
        foreach($array as $key => $value) {
            $formattedData[] = [
                $value['http_status_code'],
                $value['url'],
                $value['img_count'],
                $value['int_link_count'],
                $value['ext_link_count'],
                $value['load_time'],
                $value['page_word_count'],
                $value['page_title_length']
            ];
        }
        return $formattedData;
    }

  
    /**
     * getAverageData
     *
     * @param  array $array
     * @param  string $index
     * @return int
     */
    public static function getAverageData($array, $index): int|float
    {
        $count = count($array);
        $average = array($index => 0); 
        
        foreach($array as $value) {
          $average[$index] += $value[$index];
        }
        
        $average[$index] = ($average[$index] ? ($average[$index]/$count) : 0);

        return $average[$index];
    }
}
