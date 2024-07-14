<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;

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
        // $imageData = base64_encode(file_get_contents($imagePath));
        $imageBinary = unpack("H*", $imageData)[1];

        // Implementasi Algoritma Huffman Code
        $frequency = $this->calculateFrequency($imageBinary);
        $huffmanTree = $this->buildHuffmanTree($frequency);
        $huffmanCodes = $this->generateHuffmanCodes($huffmanTree);

        $compressedData = $this->compressData($imageBinary, $huffmanCodes);
        $decom = $this->decompressImage($compressedData, $huffmanTree);
        $decompressedImagePath = storage_path('app/public/decompressed_image.png');
        file_put_contents($decompressedImagePath, $decom);
        dd($decom);
        // Simpan data ke database
        $imageModel = new Image();
        $imageModel->original_filename = $image->getClientOriginalName();
        $imageModel->compressed_data = $compressedData;
        $imageModel->original_size = strlen($imageBinary);
        $imageModel->compressed_size = strlen($compressedData);
        $imageModel->compression_ratio = strlen($imageBinary) / strlen($compressedData);
        $imageModel->save();

        return response()->json([
            'original_size' => $imageModel->original_size,
            'compressed_size' => $imageModel->compressed_size,
            'compression_ratio' => $imageModel->compression_ratio,
            'huffman_codes' => $huffmanCodes,
        ]);
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
        // $heap = new \SplPriorityQueue();
        // foreach ($frequency as $char => $freq) {
        //     $heap->insert([$char, $freq, null, null], -$freq);
        // }

        // while ($heap->count() > 1) {
        //     $left = $heap->extract();
        //     $right = $heap->extract();

        //     // Insert combined node back into the heap
        //     $combinedFreq = $left[1] + $right[1];
        //     $heap->insert([null, $combinedFreq, $left, $right], -$combinedFreq);
        // }

        // // Return the remaining node in the heap, which represents the Huffman tree
        // return $heap->count() > 0 ? $heap->extract() : null;
        $heap = new \SplPriorityQueue();

        // Masukkan setiap karakter dengan frekuensinya ke dalam antrian prioritas
        foreach ($frequency as $char => $freq) {
            $heap->insert(new HuffmanNode($char, $freq), -$freq);
        }

        // Bangun pohon Huffman
        while ($heap->count() > 1) {
            $left = $heap->extract();
            $right = $heap->extract();

            // Gabungkan simpul untuk membentuk simpul baru dengan frekuensi yang merupakan jumlah dari kedua frekuensi sebelumnya
            $combinedFreq = $left->freq + $right->freq;
            $newNode = new HuffmanNode(null, $combinedFreq, $left, $right);

            // Masukkan simpul baru kembali ke dalam antrian prioritas
            $heap->insert($newNode, -$combinedFreq);
        }

        // Sisa simpul dalam antrian prioritas adalah akar dari pohon Huffman
        return $heap->extract();
    }

    private function printTree($tree, $prefix = '')
    {
        if (is_array($tree)) {
            if ($tree[0] !== null) {
                echo $prefix . $tree[0] . ' (' . $tree[1] . ')' . PHP_EOL;
            } else {
                echo $prefix . '*' . ' (' . $tree[1] . ')' . PHP_EOL;
            }
            $this->printTree($tree[2], $prefix . '0');
            $this->printTree($tree[3], $prefix . '1');
        }
    }

    // private function generateHuffmanCodes($tree, $prefix = "")
    // {
    //     $codes = [];
    //     if ($tree === null) {
    //         return $codes; // Return empty codes if the tree is null
    //     }

    //     if (is_string($tree[0])) {
    //         $codes[$tree[0]] = $prefix;
    //     } else {
    //         if ($tree[2] !== null) {
    //             $codes = array_merge($codes, $this->generateHuffmanCodes($tree[2], $prefix . "0"));
    //         }
    //         if ($tree[3] !== null) {
    //             $codes = array_merge($codes, $this->generateHuffmanCodes($tree[3], $prefix . "1"));
    //         }
    //     }

    //     return $codes;
    // }
    function generateHuffmanCodes($root, $currentCode = '', &$codes = [])
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

    // private function compressData($data, $codes)
    // {
    //     $compressedData = "";
    //     $binaryLength = strlen($data);

    //     // Convert each byte (2 characters) back to character and compress using Huffman codes
    //     for ($i = 0; $i < $binaryLength; $i += 2) {
    //         $byte = substr($data, $i, 2); // Get each byte (2 characters)
    //         $char = pack("H*", $byte);   // Convert hex byte back to character

    //         if (isset($codes[$char])) {
    //             $compressedData .= $codes[$char];
    //         } else {
    //             echo "Character causing issue: " . $char . "\n";
    //             // Handle the case where the code for a character is not found
    //             throw new \Exception("Huffman code not found for character: $char");
    //         }
    //     }
    //     return $compressedData;
    // }
    private function compressData($data, $codes)
    {
        $compressedData = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if (isset($codes[$char])) {
                $compressedData .= $codes[$char];
            } else {
                // Handle the case where the code for a character is not found
                throw new \Exception("Huffman code not found for character: $char");
            }
        }
        return $compressedData;
    }
    public function decompressImage($compressedData, $huffmanTree)
    {
        // Ambil data dari database
        // $imageModel = Image::find($id);
        // if (!$imageModel) {
        //     return response()->json(['error' => 'Image not found'], 404);
        // }

        // Ambil data terkompresi dan pohon Huffman dari model
        // $compressedData = $compressedData;
        // // $huffmanTree = json_decode($imageModel->huffman_tree, true);
        // $huffmanTree = $huffmanTree;

        // Dekompresi menggunakan HuffmanDecoder
        $decoder = new HuffmanDecoder($huffmanTree);
        $decompressedData = $decoder->decompress($compressedData);

        // Ubah data yang didekompresi menjadi gambar
        $imageData = pack("H*", $decompressedData);

        return $imageData;
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
