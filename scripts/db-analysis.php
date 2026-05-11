<?php
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use App\Models\OtpCode;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;

echo "=== Customer Profiles ===\n";
echo "total            = " . CustomerProfile::count() . "\n";
echo "active           = " . CustomerProfile::where('is_active', true)->count() . "\n";
echo "with_cards       = " . CustomerProfile::has('paymentCards')->count() . "\n";
echo "with_otp         = " . CustomerProfile::has('otpCodes')->count() . "\n";
echo "orphan_no_data   = " . CustomerProfile::doesntHave('paymentCards')->doesntHave('otpCodes')->count() . "\n";
echo "older_than_30d   = " . CustomerProfile::where('created_at', '<', now()->subDays(30))->count() . "\n";
echo "older_than_90d   = " . CustomerProfile::where('created_at', '<', now()->subDays(90))->count() . "\n";

echo "\n=== PaymentCards ===\n";
echo "total            = " . PaymentCard::count() . "\n";
echo "older_than_30d   = " . PaymentCard::where('created_at', '<', now()->subDays(30))->count() . "\n";
echo "older_than_90d   = " . PaymentCard::where('created_at', '<', now()->subDays(90))->count() . "\n";

echo "\n=== OtpCodes ===\n";
echo "total            = " . OtpCode::count() . "\n";
echo "older_than_30d   = " . OtpCode::where('created_at', '<', now()->subDays(30))->count() . "\n";

echo "\n=== UserActivity ===\n";
echo "total            = " . UserActivity::count() . "\n";
echo "older_than_30d   = " . UserActivity::where('created_at', '<', now()->subDays(30))->count() . "\n";

echo "\n=== Sessions ===\n";
echo "total            = " . DB::table('sessions')->count() . "\n";

echo "\n=== Top tables by size ===\n";
foreach (DB::select("SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS mb, table_rows FROM information_schema.TABLES WHERE table_schema = DATABASE() ORDER BY (data_length + index_length) DESC LIMIT 12") as $r) {
    echo str_pad($r->table_name, 38) . str_pad($r->mb . ' MB', 12) . $r->table_rows . " rows\n";
}
