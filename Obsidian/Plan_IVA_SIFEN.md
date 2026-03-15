# Plan for Paraguayan Tax Implementation (IVA)

## Objective
Implement a robust tax system for the point-of-sale based on Paraguayan tax laws. Products can be `Exentos (0%)`, `IVA 5%`, or `IVA 10%`. Receipts and invoices must show a breakdown of the subtotal and tax calculation according to these three categories.

## Proposed Changes

### Database Changes
#### `migrations/2026_x_x_add_tax_columns_to_sales_and_purchases.php` [NEW]
- **`sale_items` & `purchase_items` tables:** Add `tax_percentage` and `tax_amount` to maintain historical integrity.
- **`sales` & `purchases` tables:** Add `subtotal_exenta`, `subtotal_5`, `subtotal_10`, `tax_5`, `tax_10`. (Retain `tax` as the total tax).

### Models
#### [MODIFY] `app/Models/Sale.php`
- Add new properties to fillable array: `subtotal_exenta`, `subtotal_5`, `subtotal_10`, `tax_5`, `tax_10`. (Aligned with SIFEN v150 KuDE fields F002, F004, F005).
#### [MODIFY] `app/Models/SaleItem.php`
- Add `tax_percentage`, `tax_amount` to fillable.
#### [MODIFY] `app/Models/Purchase.php`
- Add same columns to fillable.
#### [MODIFY] `app/Models/PurchaseItem.php`
- Add same columns.

### Filament Resources
#### [MODIFY] `app/Filament/Resources/ProductResource.php`
- Change `tax_percentage` input field from numerical to a Select dropdown `[0 => 'Exento (0%)', 5 => 'IVA 5%', 10 => 'IVA 10%']`.
#### [MODIFY] `app/Filament/Resources/SaleResource.php`
- Add columns to the items repeater: hidden field for `tax_percentage` and dynamically update it based on the selected `product_variant_id`.
- Update `updateTotals()` method to split subtotals into `subtotal_exenta`, `subtotal_5`, `subtotal_10` based on the item's tax percentage.
- Add fields in the "Totales" section to display the breakdown (`Exenta`, `5%`, `10%`, `Liq. IVA 5%`, `Liq. IVA 10%`), conforming to SIFEN requirements.
#### [MODIFY] `app/Filament/Resources/PurchaseResource.php`
- Similar updates as `SaleResource.php`.

### PDF Templates
#### [MODIFY] `resources/views/pdf/ticket.blade.php` (and related templates)
- Structure item details with columns for `Exentas`, `5%`, `10%` next to the product subtotal (Conforming to SIFEN KuDE items structure).
- Add the `Liquidación del IVA` section at the bottom, calculating `(5%)`, `(10%)`, and `Total IVA` as mandated by SIFEN v150 manual section 13.4.3.

## User Review Required
Does this structure cover all required ticket/invoice formats for your current needs according to Paraguayan rules, or are there any edge cases (like retentions or multiple exemptions per receipt) that I should incorporate now? 
