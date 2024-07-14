<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SplPriorityQueue;

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
        $imageData = file_get_contents($image->getPathname());

        $frequency = $this->calculateFrequency($imageData);

        $huffmanTree = $this->buildHuffmanTree($frequency);

        $huffmanTable = $this->buildHuffmanTable($huffmanTree);
        $encodedImage = $this->encodeImage($imageData, $huffmanTable);

        $compressedImagePath = storage_path('app/public/compressed_image.bin');
        file_put_contents($compressedImagePath, $encodedImage);

        return "Gambar berhasil dikompresi. Hasil kompresi tersimpan di: " . $compressedImagePath;
    }


    public function showCompressedImage()
    {
        $compressedImagePath = storage_path('app/public/compressed_image.bin');

        if (!Storage::exists('public/compressed_image.bin')) {
            abort(404);
        }

        $bitstream = file_get_contents($compressedImagePath);

        $frequency = $this->calculateFrequency($bitstream);
        $huffmanTree = $this->buildHuffmanTree($frequency);

        $decodedImage = $this->decodeImage($bitstream, $huffmanTree);

        $decompressedImagePath = storage_path('app/public/decompressed_image.jpg');
        file_put_contents($decompressedImagePath, $decodedImage);

        return view('dashboard.decompressed', ['imagePath' => asset('storage/decompressed_image.jpg')]);
    }

    private function calculateFrequency($data)
    {
        $frequency = [];
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $byte = ord($data[$i]);
            if (isset($frequency[$byte])) {
                $frequency[$byte]++;
            } else {
                $frequency[$byte] = 1;
            }
        }

        return $frequency;
    }

    private function buildHuffmanTree($frequency)
    {
        $priorityQueue = new SplPriorityQueue();

        foreach ($frequency as $symbol => $freq) {
            $priorityQueue->insert(new HuffmanNode($symbol, $freq), -$freq);
        }

        while ($priorityQueue->count() > 1) {
            $left = $priorityQueue->extract();
            $right = $priorityQueue->extract();

            $parent = new HuffmanNode(null, $left->frequency + $right->frequency, $left, $right);
            $priorityQueue->insert($parent, -$parent->frequency);
        }

        return $priorityQueue->extract();
    }

    private function buildHuffmanTable($node, $prefix = '')
    {
        $table = [];

        if ($node->symbol !== null) {
            $table[$node->symbol] = $prefix;
        } else {
            $table = array_merge(
                $this->buildHuffmanTable($node->left, $prefix . '0'),
                $this->buildHuffmanTable($node->right, $prefix . '1')
            );
        }

        return $table;
    }

    private function encodeImage($data, $huffmanTable)
    {
        $encodedData = '';

        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($data[$i]);
            $encodedData .= $huffmanTable[$byte];
        }

        return $encodedData;
    }

    private function decodeImage($bitstream, $huffmanTree)
    {
        $decodedData = '';
        $currentNode = $huffmanTree;

        for ($i = 0; $i < strlen($bitstream); $i++) {
            if ($bitstream[$i] === '0') {
                $currentNode = $currentNode->left;
            } else {
                $currentNode = $currentNode->right;
            }

            if ($currentNode->symbol !== null) {
                $decodedData .= chr($currentNode->symbol);
                $currentNode = $huffmanTree;
            }
        }


        $tempImagePath = tempnam(sys_get_temp_dir(), 'decompressed_image');
        file_put_contents($tempImagePath, $decodedData);
        $imageResource = getimagesizefromstring($tempImagePath);
        dd($imageResource);
        unlink($tempImagePath);

        return $imageResource;
    }
}

class HuffmanNode
{
    public $symbol;
    public $frequency;
    public $left;
    public $right;

    public function __construct($symbol, $frequency, $left = null, $right = null)
    {
        $this->symbol = $symbol;
        $this->frequency = $frequency;
        $this->left = $left;
        $this->right = $right;
    }
}
