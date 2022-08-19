<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\UtilityController;
use DOMDocument;

class MindMapController extends Controller
{
    /**
     * Return dashboard view
     *
     * @return void
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Process ajax request
     *
     * @param  mixed $request
     * @return void
     */
    public function crawler(Request $request)
    {
        // Check if arguments are passed.
        if(!$request->url ||
           !$request->count) {
            return;
        }

        // Remove '/' from end of the url if exists
        $formattedUrl = UtilityController::formatUrl($request->url);

        // Create a new DOM Document to hold our webpage structure
        $dom = new DOMDocument;

        // Load the url's contents into the DOM
        @$dom->loadHTMLFile($formattedUrl);

        // Get all anchor tags in the dom. Loop thorugh it add it to allWebLinks array.
        $anchors = $dom->getElementsByTagName('a');

        // Fetch random links from list of anchor tags
        $selectedLinks = UtilityController::getRandomWebLinks($anchors, $request->count, $formattedUrl);
        
        // Get processed data from randomly selected links
        $processedLinks = UtilityController::getProcessedData($selectedLinks);

        // Get formatted data ready for DataTables
        $tableData = UtilityController::getFormattedData($processedLinks);

        // Calculate average data
        $averageLoadTime = UtilityController::getAverageData($processedLinks, 'load_time');
        $averageWordCount = UtilityController::getAverageData($processedLinks, 'page_word_count');
        $averagePageTitleLength = UtilityController::getAverageData($processedLinks, 'page_title_length');

        return response()->json([
            'table_data' => $tableData,
            'average_load_time' => round($averageLoadTime, 3),
            'average_word_count' => round($averageWordCount),
            'average_page_title_length' => round($averagePageTitleLength),
        ]);
    }
}
