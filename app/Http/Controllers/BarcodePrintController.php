<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BarcodePrintController extends Controller
{
    public function printBarcode(Request $request)
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'product_name' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'integer|min:1'
        ]);

        // TSC TTP-225 specific commands
        $commands = [
            'SIZE 1.50 in, 1.02 in',       // Match your label size
            'GAP 0.12 in, 0.00 in',         // Match your gap settings
            'DIRECTION 1',                  // Print direction
            'CLS',                          // Clear buffer
            'BARCODE 20,40,"128",50,1,0,2,2,"' . $validated['barcode'] . '"',
            // Barcode position and settings
            'TEXT 20,100,"0",0,1,1,"Rs. ' . number_format($validated['price'], 2) . '"',
            // Price position
            'PRINT ' . ($validated['quantity'] ?? 1),
            'END'
        ];

        $tspl = implode("\n", $commands);

        // Save to file (for network printing)
        $filename = 'barcode_' . time() . '.prn';
        file_put_contents(storage_path('app/prints/' . $filename), $tspl);

        // Option 1: Direct printing (if printer is connected to server)
        // exec('print /D:"\\\\server\\printer" '.storage_path('app/prints/'.$filename));

        // Option 2: Return file for download
        return response()->json([
            'file' => $filename,
            'tspl' => $tspl // For debugging
        ]);
    }
    private function printToNetworkPrinter($filename)
    {
        $printerIP = config('printing.tsc.printer_ip');
        $command = 'lpr -S ' . $printerIP . ' -P raw ' . storage_path('app/prints/' . $filename);
        exec($command);
    }
}
