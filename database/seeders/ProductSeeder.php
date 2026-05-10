<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RfidTag;
use App\Models\ChargingSession;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Hardware Product (RFID Tags)
        $tagProduct = Product::firstOrCreate(
            ['internal_code' => 'NFC-001'],
            [
                'name' => 'Tarjeta NFC Estándar',
                'siat_product_code' => '123456', // Placeholder SIN code
                'price' => 50.00,
                'unit_of_measure' => 'UNIDAD',
                'type' => 'fixed',
                'category' => 'Hardware',
            ]
        );

        // 2. Create Default Service Product (Energy)
        $energyProduct = Product::firstOrCreate(
            ['internal_code' => 'ENERGY-SVC'],
            [
                'name' => 'Servicio de Carga de Energía Eléctrica',
                'siat_product_code' => '654321', // Placeholder SIN code
                'price' => null, // Variable
                'unit_of_measure' => 'SERVICIO',
                'type' => 'service',
                'category' => 'Energy',
            ]
        );

        // 3. Create Connection Fee Product
        $connectionProduct = Product::firstOrCreate(
            ['internal_code' => 'CONN-FEE'],
            [
                'name' => 'Servicio de Conexión / Parking',
                'siat_product_code' => '123458',
                'price' => 0.00,
                'unit_of_measure' => 'SERVICIO',
                'type' => 'service',
                'category' => 'Charging',
            ]
        );

        // 4. Create Time Fee Product
        $timeProduct = Product::firstOrCreate(
            ['internal_code' => 'TIME-PENALTY'],
            [
                'name' => 'Recargo por Tiempo Excedido',
                'siat_product_code' => '123459',
                'price' => 0.00,
                'unit_of_measure' => 'SERVICIO',
                'type' => 'service',
                'category' => 'Charging',
            ]
        );

        // 5. Create Virtual Tag Product (App)
        $virtualTagProduct = Product::firstOrCreate(
            ['internal_code' => 'VIRTUAL-TAG'],
            [
                'name' => 'Tag Virtual App',
                'siat_product_code' => '123457',
                'price' => 0.00,
                'unit_of_measure' => 'SERVICIO',
                'type' => 'service',
                'category' => 'Identification',
            ]
        );

        // 6. Migrate existing Tags
        RfidTag::whereNull('product_id')->update(['product_id' => $tagProduct->id]);

        // 7. Migrate existing Sessions
        ChargingSession::whereNull('product_id')->update(['product_id' => $energyProduct->id]);

        // 8. Create Wallet Recharge Product
        Product::firstOrCreate(
            ['internal_code' => 'RECHARGE'],
            [
                'name' => 'Recarga de Billetera Móvil',
                'siat_product_code' => '99100', // Placeholder SIN code
                'price' => null,
                'unit_of_measure' => 'SERVICIO',
                'type' => 'service',
                'category' => 'Finance',
            ]
        );
    }
}
