<?php
// ------------------------------
// components/review-dialog.php
// Review/Question dialog modal component
// ------------------------------
?>

<?php if ($isLoggedIn): ?>
<div class="dialog-overlay" id="dialogOverlay">
  <div class="dialog-box">
    <form method="POST" id="dialogForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div id="reviewForm">
        <h3>Add Review</h3>
        <label>Rating:</label>
        <select name="rating" required>
          <option value="">Select rating</option>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i; ?>"><?= $i; ?> ★</option>
          <?php endfor; ?>
        </select>
        <label>Comment:</label>
        <textarea name="comment" rows="3" placeholder="Write your review..." required maxlength="1000"></textarea>
        <button type="submit" name="submit_review" class="add-btn">Submit</button>
      </div>

      <div id="questionForm" style="display:none;">
        <h3>Ask a Question</h3>
        <textarea name="question" rows="3" placeholder="Ask about this product..." required maxlength="1000"></textarea>
        <button type="submit" name="submit_question" class="add-btn">Post Question</button>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="cancel-btn" id="closeDialogBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>