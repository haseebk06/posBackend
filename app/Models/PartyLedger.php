<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyLedger extends Model
{
    protected $fillable = [
        'customer_id',
        'location_from',
        'location_to',
        'billing_date_from',
        'billing_date_to',
        'po_from_invoice_id',
        'po_to_invoice_id',
        'port_name',
        'lot_number',
        'vessel_name',
        'serial_number',
        'coil',
        'ledger_date',
        'truck_no',
        'weight',
        'rate',
        'amount',
        'name',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function poFromInvoice()
    {
        return $this->belongsTo(Invoice::class, 'po_from_invoice_id');
    }

    public function poToInvoice()
    {
        return $this->belongsTo(Invoice::class, 'po_to_invoice_id');
    }
}
