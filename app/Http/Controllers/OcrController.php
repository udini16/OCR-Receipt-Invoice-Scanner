<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Receipt;
use App\Models\LineItem;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrController extends Controller
{
    protected $ocr;

    public function home(){
        return view('OCR');
    }

    public function readImage(Request $request){
        $image = $request->file("image");
        if(isset($image) && $image->getPathName()){
            $ocr = new TesseractOCR();
            $ocr->lang('eng', 'jpn', 'spa', 'deu');
            $ocr->image($image->getPathName());

            $parsedText = $ocr->run();

            $invoice = $this->extractInvoice($parsedText);
            $date = $this->extractDate($parsedText);
            $address = $this->extractAddress($parsedText);
            $merchant = $this->extractMerchantName($parsedText);
            $lineItems = $this->extractItemsInfo($parsedText);
            $total = $this->extractTotal($parsedText);


            $parsedTextModel = new Receipt();
            $parsedTextModel->text_data = $parsedText;
            $parsedTextModel->date = $date;
            $parsedTextModel->address=$address;
            $parsedTextModel->invoice_number=$invoice;
            $parsedTextModel->merchant=$merchant;
            $parsedTextModel->total_price=$total;
            $parsedTextModel->save();

            $itemsInfo = $this->extractItemsInfo($parsedText);

            $lineItems = [];
            foreach ($itemsInfo as $itemInfo) {
                $lineItem = [
                    'item_name' => $itemInfo['item_name'],
                    'quantity' => isset($itemInfo['quantity']) ? (int) str_replace(',', '', $itemInfo['quantity']) : null,
                    'price' => (float) $itemInfo['price'], // Convert to float
                    'receipts_id' => $parsedTextModel->id,
                ];

                $lineItems[] = $lineItem;
            }

            LineItem::insert($lineItems);

            return view('parsed_text', compact('parsedText'));
        }
    }

    function extractInvoice($text) {
        $pattern1 = '/INVOICE NO\.\s*:\s*(\S+)/';
        $pattern2 = '/Iv-\d+/';
        $pattern3 = '/Invoice Number:\s*(\S+)/';
        $pattern4 = '/INVOICE NO\. —\s*:\s*(\S+)/';
        $pattern5 = '/INVOICE, (INV\d+)/';
        $pattern6 = '/Invoice No (INV\d+)/';
        $pattern7 = '/INVNo (M\d+)/';
        $pattern8 = '/Inv\.no\s*—-:\s*([\d-]+)/i';
    
        if (preg_match($pattern1, $text, $matches1)) {
            return $matches1[1]; // Format 1: "INVOICE NO: ..."
        } elseif (preg_match($pattern2, $text, $matches2)) {
            return $matches2[0]; // Format 2: "Iv-..."
        } elseif (preg_match($pattern3, $text, $matches3)) {
            return $matches3[1]; // Format 3: "Invoice Number: ..."
        } elseif (preg_match($pattern4, $text, $matches4)) {
            return $matches4[1]; // Format 4: "INVOICE NO. — ..."
        } elseif (preg_match($pattern5, $text, $matches5)) {
            return $matches5[1]; // Format 5: "INVOICE, INV1001"
        } elseif (preg_match($pattern6, $text, $matches6)) {
            return $matches6[1]; // Format 6: "Invoice No INV1001"
        } elseif (preg_match($pattern7, $text, $matches7)) {
            return $matches7[1]; // Format 7: "INVNo M2001"
        } elseif (preg_match($pattern8, $text, $matches8)) {
            return $matches8[1]; // Format 8; Inv.no --: "2310-1034"
        }
    
        return null; 
    }
    
    function extractDate($text) {
        $dateFormats = [
            'd/m/Y',
            'm/d/Y',
            'Y-m-d',
        ];
        foreach ($dateFormats as $format) {
            $pattern = "/\b(\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2})\b/";
    
            if (preg_match($pattern, $text, $matches)) {
                $parsedDate = DateTime::createFromFormat($format, $matches[0]);
    
                if ($parsedDate) {
                    return $parsedDate->format('Y-m-d');
                }
            }
        }
        return null;
    }
    
    
    function extractAddress($text) {
        $pattern1 = '/(?<=\n)[A-Z][A-Z\s0-9,-]+(?=\n)/';
        $pattern2 = '/^(.*?),\s*([\s\S]+)/';  
        $pattern3 = '/^(.*\s){1}((.*\s){2})/';   
        $pattern4 = '/^(.*),\n(.*)/';            
        $pattern5 = '/^(.*),\s(.*),\s(.*),\s(\d{5}\s[\w\s]+)/'; 
    
        if (preg_match($pattern1, $text, $matches)) {
            return $matches[0];
        } elseif (preg_match($pattern2, $text, $matches)) {
            return $matches[2];
        } elseif (preg_match($pattern3, $text, $matches)) {
            return $matches[2];
        } elseif (preg_match($pattern4, $text, $matches)) {
            return $matches[2];
        }elseif (preg_match($pattern4, $text, $matches)) {
            return $matches[4];
        }elseif (preg_match($pattern5, $text, $matches)) {
            return $matches[2];
        }
        
        
        return null;
    }
    
    
    public function extractMerchantName($text) {
        $merchantKeywords = [
            'Merchant:',
            'Seller:',
            'Store:',
            'Shop:',
            'Vendor:',
            'Clinic:',
            'Medical Center:',
            'Healthvare Facility:',
            'Hospital:',
            'Klinik',
            'Company:',
            'Service Provider:',
            'Provider Name:',
            'Business Name:',
            'Practice:',
            'Center:',
            'Location:',
            'Entity:',
            'Factility Name:',
            'Office:',
            'Practitioner:',
            'Entity Name:',
            'Supplier:',
            'Place:',
            'Operated By:',
        ];

        $incPattern = '/\b(.*?Inc\.|.*?(?:Sdn Bhd|Services)(?:\s[\w-]+)?)(?=\s|$)/i';
    
        if (preg_match($incPattern, $text, $matches)) {
            return trim($matches[1]);
        }   

        foreach ($merchantKeywords as $keyword) {
            $pattern = "/\b($keyword\s+.*?[\w\s-]+)(\s|$)/i";
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }
    
    function extractItemsInfo($text) {
        $pattern1 = '/(\d+)\s(.+?)\s(\d+\.\d+)\s(UNIT|TIME)\s([\d,]+\.\d+)/';
        $pattern2 = '/(.+?)\s+RM(\d+\.\d+)\s+(?:x\s+RM(\d+\.\d+))?/m';
        $pattern3 = '/^[\d\s.]+ ([A-Z][A-Z\s0-9-]+)/m';
        $pattern4 = '/(\d+)\s(.+?)\s(\d+(?:x\d+)?)\s([\d,]+\.\d+)/';


        $itemsInfo = [];
    
        preg_match_all($pattern1, $text, $matches1, PREG_SET_ORDER);
        preg_match_all($pattern2, $text, $matches2, PREG_SET_ORDER);
        preg_match_all($pattern3, $text, $matches3, PREG_SET_ORDER);
        preg_match_all($pattern4, $text, $matches4, PREG_SET_ORDER);

        foreach ($matches1 as $match) {
            $quantity = (float)$match[1];
            $item_name = trim($match[2]);
            $price = (float)str_replace(',', '', $match[5]);

            $item = [
                'quantity' => $quantity,
                'item_name' => $item_name,
                'price' => $price,
            ];

            $itemsInfo[] = $item;
        }
    
        foreach ($matches2 as $match) {
            $item = [
                'item_name' => trim($match[1]),
                'price' => (float)str_replace(',', '', $match[2]),
            ];
            $itemsInfo[] = $item;
        }

        foreach ($matches3 as $match) {
            $item = ['item_name' => trim($match[1])];

            if (isset($match[2])) {
                $item['quantity'] = (int)str_replace(',', '', $match[2]);
                $item['price'] = (float)str_replace(',', '', $match[3]);
            } else {
                $item['quantity'] = null;
                $item['price'] = null;
            }
            $itemsInfo[] = $item;
        }

        foreach ($matches4 as $match) {
            $quantity = (int)$match[1];
            $item_name = trim($match[2]);
            $price = (float)str_replace(',', '', $match[4]);
    
            $item = [
                'quantity' => $quantity,
                'item_name' => $item_name,
                'price' => $price,
            ];
    
            $itemsInfo[] = $item;
        }
    
        return $itemsInfo;
    }
    

    public function extractTotal($text) {
        $pattern1 = '/TOTAL RM(\d+\.\d+)/i';
        $pattern2 = '/Total\s*\|\s*([0-9,.]+)\s*/';
        if (preg_match($pattern1, $text, $matches1)) {
            return $matches1[1];
        }
        elseif(preg_match($pattern2, $text, $matches2)){
            return $matches2[1];
        }
    
        return null;
    }
}