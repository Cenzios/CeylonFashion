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

$allColors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White', 'Pink', 'Purple', 'Orange', 'Grey', 'Brown', 'Gold', 'Silver'];
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
                        <input type="checkbox" name="colors[]" value="<?= $color ?>" <?= in_array($color, $colors) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <?= $color ?>
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
                        <input type="checkbox" name="categories[]" value="<?= $val ?>" <?= in_array($val, $cats) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <?= $label ?>
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
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    .filter-heading {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }
    .filter-group {
        margin-bottom: 25px;
    }
    .filter-subheading {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #555;
    }
    .price-inputs {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .price-inputs input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .checkbox-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #444;
        cursor: pointer;
    }
    .checkbox-item input {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-apply {
        flex: 1;
        background: #7c3aed;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.2s;
    }
    .btn-apply:hover {
        background: #6d28d9;
    }
    .btn-reset {
        flex: 1;
        background: #f3f4f6;
        color: #4b5563;
        text-decoration: none;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        font-weight: 600;
        transition: background 0.2s;
        border: 1px solid #e5e7eb;
    }
    .btn-reset:hover {
        background: #e5e7eb;
    }
</style>
