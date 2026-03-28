<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Export Controller — تصدير البيانات إلى CSV
 *
 * Exports:
 * - customers: بيانات العملاء الأساسية
 * - payments:  بيانات بطاقات الدفع
 */
class ExportController extends Controller
{
    /**
     * تصدير بيانات العملاء
     * GET /api/admin/export/customers
     */
    public function customers(Request $request)
    {
        $customers = CustomerProfile::query()
            ->orderByDesc('created_at')
            ->cursor()
            ->map(function ($customer) {
                return [
                    'ID'             => $customer->id,
                    'National ID'    => $customer->national_id ?? $customer->nafath_username,
                    'Phone'          => $customer->phone_number,
                    'Status'         => $customer->is_active ? 'Active' : 'Inactive',
                    'Current Step'   => $customer->current_step,
                    'Completion %'   => $customer->journey_completion_percentage ?? $customer->completion_percentage,
                    'Country'        => $customer->country,
                    'City'           => $customer->city ?? $customer->location_city,
                    'Device'         => $customer->device_type,
                    'Total Visits'   => $customer->total_visits,
                    'Created At'     => $customer->created_at?->format('Y-m-d H:i:s'),
                    'Last Activity'  => $customer->last_activity_at?->format('Y-m-d H:i:s'),
                ];
            });

        return $this->downloadCsv($customers, 'customers');
    }

    /**
     * تصدير بيانات المدفوعات
     * GET /api/admin/export/payments
     */
    public function payments(Request $request)
    {
        $payments = PaymentCard::with('customer')
            ->orderByDesc('created_at')
            ->cursor()
            ->map(function ($payment) {
                return [
                    'ID'            => $payment->id,
                    'Customer ID'   => $payment->customer_profile_id,
                    'Card Display'  => $payment->card_display,
                    'Card Type'     => $payment->card_type,
                    'Holder'        => $payment->holder_name,
                    'Status'        => $payment->status,
                    'Reviewed By'   => $payment->reviewed_by,
                    'Reviewed At'   => $payment->reviewed_at?->format('Y-m-d H:i:s'),
                    'Created At'    => $payment->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return $this->downloadCsv($payments, 'payments');
    }

    /**
     * Helper: تحويل البيانات إلى CSV وتنزيلها
     */
    private function downloadCsv(iterable $data, string $filename)
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}_" . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Write BOM for UTF-8 (Excel compatibility)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write headers from first row, then iterate all rows
            $headerWritten = false;
            foreach ($data as $row) {
                if (! $headerWritten) {
                    fputcsv($file, array_keys($row));
                    $headerWritten = true;
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
