<?php

namespace App\Http\Controllers;

use Intervention\Image\Laravel\Facades\Image;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function compressImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $image = $request->file('image');
        $imagePath = $image->getRealPath();
        $imageData = file_get_contents($imagePath);
        $imageHex = bin2hex($imageData);

        $frequency = $this->calculateFrequency($imageHex);
        $huffmanTree = $this->buildHuffmanTree($frequency);
        $huffmanCodes = $this->generateHuffmanCodes($huffmanTree);

        $pixelImage = $this->calculatePixelFrequency($imagePath);
        dd($pixelImage);
        $compressedData = $this->compressData($imageHex, $huffmanCodes);

        $decompressedData = $this->decompressImage($compressedData, $huffmanTree);

        $decompressedImageData = hex2bin($decompressedData);

        $decompressedImagePath = storage_path('app/public/decompressed_image.png');
        file_put_contents($decompressedImagePath, $decompressedImageData);



        return response()->json([
            'message' => 'sukses',
        ]);
    }

    public function createFrequencyMatrix($imagePath)
    {
        $pixelFrequency = $this->calculatePixelFrequency($imagePath);


        $imageManager = new ImageManager(new Driver());
        dd($imageManager);
        $image = $imageManager::make($imagePath);
        $width = $image->width();
        $height = $image->height();

        $frequencyMatrix = [];

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $pixel = $image->pickColor($x, $y, 'array');
                $character = implode('', $pixel);

                $frequencyMatrix[$x][$y] = $pixelFrequency[$character];
            }
        }

        return $frequencyMatrix;
    }

    private function calculatePixelFrequency($imagePath)
    {
        $imageData = file_get_contents($imagePath);

        $pixelFrequency = [];


        $image = Image::make($imagePath);
        $width = $image->width();
        $height = $image->height();

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $pixel = $image->pickColor($x, $y, 'array');
                $character = implode('', $pixel);
                if (!isset($pixelFrequency[$character])) {
                    $pixelFrequency[$character] = 0;
                }
                $pixelFrequency[$character]++;
            }
        }

        return $pixelFrequency;
    }


    private function calculateFrequency($data)
    {
        $frequency = [];
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if (!isset($frequency[$char])) {
                $frequency[$char] = 0;
            }
            $frequency[$char]++;
        }
        return $frequency;
    }

    private function buildHuffmanTree($frequency)
    {
        $heap = new \SplPriorityQueue();
        foreach ($frequency as $char => $freq) {
            $heap->insert(new HuffmanNode($char, $freq), -$freq);
        }

        while ($heap->count() > 1) {
            $left = $heap->extract();
            $right = $heap->extract();

            $combinedFreq = $left->freq + $right->freq;
            $newNode = new HuffmanNode(null, $combinedFreq, $left, $right);

            $heap->insert($newNode, -$combinedFreq);
        }

        return $heap->extract();
    }

    private function generateHuffmanCodes($root, $currentCode = '', &$codes = [])
    {

        if ($root) {
            if ($root->char !== null) {
                $codes[$root->char] = $currentCode;
            } else {
                $this->generateHuffmanCodes($root->left, $currentCode . '0', $codes);
                $this->generateHuffmanCodes($root->right, $currentCode . '1', $codes);
            }
        }
        return $codes;
    }

    private function compressData($data, $codes)
    {
        $compressedData = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if (isset($codes[$char])) {
                $compressedData .= $codes[$char];
            } else {
                throw new \Exception("Huffman code not found for character: $char");
            }
        }
        return $compressedData;
    }

    public function decompressImage($compressedData, $huffmanTree)
    {
        $decoder = new HuffmanDecoder($huffmanTree);
        $decompressedData = $decoder->decompress($compressedData);

        return $decompressedData;
    }
}

class HuffmanNode
{
    public $char;
    public $freq;
    public $left;
    public $right;

    public function __construct($char, $freq, $left = null, $right = null)
    {
        $this->char = $char;
        $this->freq = $freq;
        $this->left = $left;
        $this->right = $right;
    }
}

class HuffmanNode1
{
    public $char;
    public $freq;
    public $left;
    public $right;

    public function __construct($char = null, $left = null, $right = null)
    {
        $this->char = $char;
        $this->left = $left;
        $this->right = $right;
    }
}

class HuffmanDecoder
{
    private $root;

    public function __construct($huffmanTree)
    {
        $this->root = $this->buildTree($huffmanTree);
    }

    private function buildTree($huffmanTree)
    {
        if ($huffmanTree->char === null && $huffmanTree->left !== null && $huffmanTree->right !== null) {
            $left = $this->buildTree($huffmanTree->left);
            $right = $this->buildTree($huffmanTree->right);
            return new HuffmanNode1(null, $left, $right);
        } else {
            return new HuffmanNode1($huffmanTree->char, null, null);
        }
    }

    public function decompress($compressedData)
    {
        $currentNode = $this->root;
        $decompressedData = '';

        for ($i = 0; $i < strlen($compressedData); $i++) {
            $bit = $compressedData[$i];

            if ($bit === '0') {
                $currentNode = $currentNode->left;
            } else {
                $currentNode = $currentNode->right;
            }

            if ($currentNode->char !== null) {
                $decompressedData .= $currentNode->char;
                $currentNode = $this->root;
            }
        }

        return $decompressedData;
    }
}
