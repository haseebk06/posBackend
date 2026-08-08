<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\StoreInformation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['customer', 'storeInformation'])->latest()->get();

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $data = $this->buildInvoiceData($validated);
        $data['invoice_number'] = $validated['invoice_number'] ?? $this->nextInvoiceNumber();

        $invoice = Invoice::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Invoice created successfully',
            'data' => $invoice->load(['customer', 'storeInformation']),
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['customer', 'storeInformation'])->findOrFail($id);

        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = array_merge($invoice->toArray(), $validator->validated());
        $data = $this->buildInvoiceData($validated);
        if (array_key_exists('invoice_number', $validator->validated())) {
            $data['invoice_number'] = $validator->validated()['invoice_number'];
        }

        $invoice->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Invoice updated successfully',
            'data' => $invoice->load(['customer', 'storeInformation']),
        ]);
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json([
            'status' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];
        $invoiceNumberRules = $isUpdate
            ? ['sometimes', 'required', 'integer', 'min:1']
            : ['nullable', 'integer', 'min:1'];

        return [
            'invoice_number' => [
                ...$invoiceNumberRules,
                Rule::unique('invoices', 'invoice_number')->ignore(request()->route('id')),
            ],
            'invoice_date' => [...$required, 'date'],
            'customer_id' => [...$required, 'exists:customers,id'],
            'store_information_id' => [...$required, 'exists:store_information,id'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'vessel' => ['nullable', 'string', 'max:255'],
            'invoice_type' => ['nullable', 'string', 'in:Import,Export,IMPORT,EXPORT'],
            'size_description' => ['nullable', 'string'],
            'size_description_2' => ['nullable', 'string'],
            'invoice_details' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'weight_2' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'rate_2' => ['nullable', 'numeric', 'min:0'],
            'gross_amount' => [...$required, 'numeric', 'min:0'],
            'chq_number' => ['nullable', 'string', 'max:255'],
            'cheque_received_date' => ['nullable', 'date'],
            'received' => ['nullable', 'numeric', 'min:0'],
            'invoice_status' => ['nullable', 'in:SBR Paid,SBR Pending,SBR declined'],
        ];
    }

    private function buildInvoiceData(array $data): array
    {
        $storeInformation = StoreInformation::findOrFail($data['store_information_id']);

        $grossAmount = round((float) ($data['gross_amount'] ?? 0), 2);
        $sstPercentage = (float) ($storeInformation->sst ?? 0);
        $whTaxPercentage = (float) ($storeInformation->wh_tax_percentage ?? 0);
        $sstWithholdingTaxPercentage = (float) ($storeInformation->sst_withholding_tax_percentage ?? 0);

        $sstAmount = round($grossAmount * $sstPercentage / 100, 2);
        $totalAmount = round($grossAmount + $sstAmount, 2);
        $whTaxAmount = round($totalAmount * $whTaxPercentage / 100, 2);
        $netAmount = round($totalAmount - $whTaxAmount, 2);
        $sstWithholdingTaxAmount = round($sstAmount * $sstWithholdingTaxPercentage / 100, 2);
        $netSstAmountSbr = round($sstAmount - $sstWithholdingTaxAmount, 2);
        $calculatedReceived = round($netAmount - $sstWithholdingTaxAmount, 2);
        $hasChequeNumber = !empty($data['chq_number']);
        $received = $hasChequeNumber
            ? round((float) ($data['received'] ?? $calculatedReceived), 2)
            : 0;

        return [
            'invoice_date' => $data['invoice_date'],
            'customer_id' => $data['customer_id'],
            'store_information_id' => $data['store_information_id'],
            'po_number' => $data['po_number'] ?? null,
            'lot_number' => $data['lot_number'] ?? null,
            'vessel' => $data['vessel'] ?? null,
            'invoice_type' => $data['invoice_type'] ?? null,
            'size_description' => $data['size_description'] ?? null,
            'size_description_2' => $data['size_description_2'] ?? null,
            'invoice_details' => $data['invoice_details'] ?? null,
            'weight' => $data['weight'] ?? 0,
            'weight_2' => $data['weight_2'] ?? null,
            'rate' => $data['rate'] ?? 0,
            'rate_2' => $data['rate_2'] ?? null,
            'gross_amount' => $grossAmount,
            'sst_percentage' => $sstPercentage,
            'sst_amount' => $sstAmount,
            'total_amount' => $totalAmount,
            'wh_tax_percentage' => $whTaxPercentage,
            'wh_tax_amount' => $whTaxAmount,
            'net_amount' => $netAmount,
            'sst_withholding_tax_percentage' => $sstWithholdingTaxPercentage,
            'sst_withholding_tax_amount' => $sstWithholdingTaxAmount,
            'net_sst_amount_sbr' => $netSstAmountSbr,
            'received' => $received,
            'chq_number' => $data['chq_number'] ?? null,
            'cheque_received_date' => $data['cheque_received_date'] ?? null,
            'invoice_status' => $data['invoice_status'] ?? 'SBR Pending',
        ];
    }

    private function nextInvoiceNumber(): int
    {
        return ((int) Invoice::max('invoice_number')) + 1;
    }
}
