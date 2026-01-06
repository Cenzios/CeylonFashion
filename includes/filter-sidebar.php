<?php
// includes/filter-sidebar.php
// Expected variables:
// $showCategoryFilter (bool) - default false
// $showColorFilter (bool) - default false
// $showPriceFilter (bool) - default true
// $selectedMinPrice, $selectedMaxPrice
// $selectedColors (array)
// $selectedCategories (array)

$showCategoryFilter = $showCategoryFilter ?? false;
$showColorFilter = $showColorFilter ?? false;
$showPriceFilter = $showPriceFilter ?? true;

$minPrice = $selectedMinPrice ?? '';
$maxPrice = $selectedMaxPrice ?? '';
$colors = $selectedColors ?? [];
$cats = $selectedCategories ?? [];

// Fetch colors from database (same as admin add product section)
$allColors = [];
if (isset($mysqli)) {
    $colorQuery = $mysqli->query("SELECT color_name FROM colors ORDER BY color_name");
    if ($colorQuery && $colorQuery->num_rows > 0) {
        while ($row = $colorQuery->fetch_assoc()) {
            $allColors[] = $row['color_name'];
        }
    }
}
// Fallback to common colors if database is empty
if (empty($allColors)) {
    $allColors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White', 'Pink', 'Purple', 'Orange', 'Grey', 'Brown', 'Gold', 'Silver'];
}

$allCategories = [
    'bridalAttire' => 'Bridal Attire', 
    'bridemaidAttire' => "Bridemaid's Attire", 
    'partyWear' => 'Party Wear', 
    'used' => 'Used Collection'
];
?>

<div class="filter-sidebar">
    <form method="GET" action="">
        <h4 class="filter-heading">Filters</h4>
        
        <?php if ($showPriceFilter): ?>
        <div class="filter-group">
            <h5 class="filter-subheading">Price Range</h5>
            <div class="price-inputs">
                <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>" min="0">
                <span>-</span>
                <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>" min="0">
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showColorFilter): ?>
        <div class="filter-group">
            <h5 class="filter-subheading">Color</h5>
            <div class="checkbox-list">
                <?php foreach ($allColors as $color): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color) ?>" <?= in_array($color, $colors) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($color) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showCategoryFilter): ?>
        <div class="filter-group">
            <h5 class="filter-subheading">Category</h5>
            <div class="checkbox-list">
                <?php foreach ($allCategories as $val => $label): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($val) ?>" <?= in_array($val, $cats) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="filter-actions">
            <button type="submit" class="btn-apply">Apply Filters</button>
            <a href="?" class="btn-reset">Reset</a>
        </div>
    </form>
</div>

<style>
    .filter-sidebar {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        position: sticky;
        top: 20px;
    }
    .filter-heading {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 25px 0;
        color: #333;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 15px;
        text-align: left;
    }
    .filter-group {
        margin-bottom: 30px;
    }
    .filter-group:last-of-type {
        margin-bottom: 25px;
    }
    .filter-subheading {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 12px 0;
        color: #374151;
        text-align: left;
    }
    .price-inputs {
        display: flex;
        gap: 12px;
        align-items: center;
        width: 100%;
    }
    .price-inputs input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .price-inputs input:focus {
        outline: none;
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .price-inputs span {
        color: #6b7280;
        font-weight: 500;
    }
    .checkbox-list {
        display: flex;
        flex-direction: column;
        gap: 0;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 8px;
        margin-right: -8px;
    }
    .checkbox-list::-webkit-scrollbar {
        width: 8px;
    }
    .checkbox-list::-webkit-scrollbar-track {
        background: #f9fafb;
        border-radius: 4px;
    }
    .checkbox-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }
    .checkbox-list::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        padding: 8px 6px;
        margin: 0;
        transition: background-color 0.2s, color 0.2s;
        border-radius: 4px;
        min-height: 36px;
        width: 100%;
    }
    .checkbox-item:hover {
        background-color: #f9fafb;
        color: #7c3aed;
    }
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        min-width: 18px;
        max-width: 18px;
        cursor: pointer;
        accent-color: #7c3aed;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
        vertical-align: middle;
    }
    .checkbox-item span {
        flex: 1;
        line-height: 1.5;
        word-wrap: break-word;
        display: inline-block;
        vertical-align: middle;
    }
    .filter-actions {
        display: flex;
        gap: 12px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    .btn-apply {
        flex: 1;
        background: #7c3aed;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s, transform 0.1s;
    }
    .btn-apply:hover {
        background: #6d28d9;
        transform: translateY(-1px);
    }
    .btn-apply:active {
        transform: translateY(0);
    }
    .btn-reset {
        flex: 1;
        background: #f3f4f6;
        color: #4b5563;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s, transform 0.1s;
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-reset:hover {
        background: #e5e7eb;
        transform: translateY(-1px);
    }
    .btn-reset:active {
        transform: translateY(0);
    }
    
    @media (max-width: 1024px) {
        .filter-sidebar {
            position: relative;
            top: 0;
        }
    }
</style>
