# ipaymu-v2/laravel

Ini adalah paket Composer untuk integrasi iPaymu v2 dengan Laravel.

## Instalasi

```bash
composer require ipaymu-v2/laravel
```

## Penggunaan

Pertama, pastikan Anda telah mengonfigurasi variabel lingkungan yang diperlukan di file `.env` aplikasi Laravel Anda seperti yang dijelaskan di bagian "Konfigurasi".

Anda dapat menginstansiasi kelas `IpaymuV2` dan menggunakan metode-metodenya:

```php
use IpaymuV2\Laravel\IpaymuV2;

$ipaymu = new IpaymuV2();
```

### Mendapatkan Saldo (getBalance)

Metode ini digunakan untuk mendapatkan saldo akun iPaymu Anda.

```php
use IpaymuV2\Laravel\IpaymuV2;

$ipaymu = new IpaymuV2();
$balance = $ipaymu->getBalance(); // Akan menggunakan IPAYMU_VA dari .env
// Atau dengan VA spesifik:
// $balance = $ipaymu->getBalance('your_specific_va');

if (isset($balance['status']) && $balance['status'] == 'success') {
    echo "Saldo Anda: " . $balance['data']['balance'];
} else {
    echo "Gagal mendapatkan saldo: " . ($balance['message'] ?? 'Terjadi kesalahan');
}
```

### Membuat Halaman Pembayaran (createPaymentPage)

Metode ini digunakan untuk membuat halaman pembayaran iPaymu.

```php
use IpaymuV2\Laravel\IpaymuV2;

$ipaymu = new IpaymuV2();

$product = ['Nama Produk 1', 'Nama Produk 2'];
$qty = [1, 2];
$price = [10000, 5000]; // Harga per unit

$name = 'Nama Pembeli';
$email = 'pembeli@example.com';
$phone = '08123456789';
$callback = 'https://your-app.com/ipaymu/notify'; // URL notifikasi dari iPaymu

$paymentPage = $ipaymu->createPaymentPage($product, $qty, $price, $name, $email, $phone, $callback);

if (isset($paymentPage['status']) && $paymentPage['status'] == 'success') {
    echo "URL Pembayaran: " . $paymentPage['data']['url'];
    // Redirect pengguna ke URL ini
} else {
    echo "Gagal membuat halaman pembayaran: " . ($paymentPage['message'] ?? 'Terjadi kesalahan');
}
```

### Membuat Pembayaran Langsung (createDirectPayment)

Metode ini digunakan untuk membuat pembayaran langsung melalui metode pembayaran tertentu.

```php
use IpaymuV2\Laravel\IpaymuV2;

$ipaymu = new IpaymuV2();

$product = ['Nama Produk'];
$qty = [1];
$price = [15000];

$name = 'Nama Pembeli';
$email = 'pembeli@example.com';
$phone = '08123456789';
$callback = 'https://your-app.com/ipaymu/notify'; // URL notifikasi dari iPaymu
$method = 'va'; // Contoh: 'va', 'qris', 'cstore'
$channel = 'bca'; // Contoh: 'bca', 'mandiri', 'indomaret', 'alfamart'

// Catatan: Untuk 'returnUrl' dan 'notifyUrl' yang menggunakan fungsi `route()` Laravel,
// Anda perlu menyediakan URL yang valid dari aplikasi Anda.
// Contoh: route('your.return.route') atau 'https://your-app.com/return-url'

$directPayment = $ipaymu->createDirectPayment($product, $qty, $price, $name, $email, $phone, $callback, $method, $channel);

if (isset($directPayment['status']) && $directPayment['status'] == 'success') {
    echo "Pembayaran Langsung Berhasil. Data: ";
    print_r($directPayment['data']);
    // Lanjutkan dengan proses pembayaran sesuai respons
} else {
    echo "Gagal membuat pembayaran langsung: " . ($directPayment['message'] ?? 'Terjadi kesalahan');
}
```

## Catatan Penting untuk URL Callback/Return

Beberapa metode (seperti `createDirectPayment` dan `edcNotify`) dalam paket ini secara internal menggunakan placeholder untuk `returnUrl` atau `notifyUrl` yang awalnya mungkin menggunakan fungsi `route()` Laravel.

**Penting:** Anda harus memastikan untuk menyediakan URL yang valid dan dapat diakses secara publik dari aplikasi Laravel Anda saat memanggil metode-metode ini. Fungsi `route()` hanya berfungsi dalam konteks aplikasi Laravel yang lengkap dan tidak dapat dieksekusi di dalam paket ini.

## Lisensi

Paket ini dilisensikan di bawah lisensi MIT.

## Konfigurasi

Untuk menggunakan paket ini, tambahkan variabel lingkungan berikut ke file `.env` aplikasi Laravel Anda:

```
IPAYMU_HOST=
IPAYMU_VA=
IPAYMU_SECRET=
```

Pastikan untuk mengganti nilai-nilai ini dengan kredensial iPaymu Anda yang sebenarnya.

