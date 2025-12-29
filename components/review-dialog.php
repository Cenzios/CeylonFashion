<?php
// ------------------------------
// components/review-dialog.php
// Review/Question dialog modal component
// ------------------------------
?>
<style>
  .visual-rating-select {
    height: auto !important;
    min-height: 48px !important;
    padding: 12px 16px !important;
    font-size: 16px !important;
    line-height: 1.5 !important;
    border: 1px solid #ced4da !important;
    border-radius: 6px !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 1rem center !important;
    background-size: 16px 12px !important;
  }
  .visual-rating-select option {
    font-size: 16px;
    padding: 10px;
  }
</style>

<?php if ($isLoggedIn): ?>
<div class="dialog-overlay" id="dialogOverlay">
  <div class="dialog-box">
    <form method="POST" id="dialogForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div id="reviewForm">
        <h3>Add Review</h3>
        <div class="mb-3">
            <label class="form-label fw-bold">Rating:</label>
            <select name="rating" class="form-select visual-rating-select" required>
              <option value="">Select rating</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i; ?>"><?= $i; ?> ★ (<?= ["", "Poor", "Fair", "Good", "Very Good", "Excellent"][$i] ?>)</option>
              <?php endfor; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Comment:</label>
            <textarea name="comment" class="form-control" rows="3" placeholder="Write your review..." required maxlength="1000"></textarea>
        </div>
        <button type="submit" name="submit_review" class="add-btn w-100">Submit</button>
      </div>

      <div id="questionForm" style="display:none;">
        <h3>Ask a Question</h3>
        <div class="mb-3">
            <textarea name="question" class="form-control" rows="3" placeholder="Ask about this product..." required maxlength="1000"></textarea>
        </div>
        <button type="submit" name="submit_question" class="add-btn w-100">Post Question</button>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="cancel-btn" id="closeDialogBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>