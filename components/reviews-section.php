<?php
// ------------------------------
// components/reviews-section.php
// Reviews display component
// ------------------------------

function render_reviews_pager($total, $perPage, $currentPage) {
  $pages = (int)ceil(max(1, $total)/$perPage);
  if ($pages <= 1) return '';
  $out = '<div class="pager">';
  for ($p = 1; $p <= $pages; $p++) {
    $params = $_GET;
    $params['pageR'] = $p;
    $link = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    $cls = $p === (int)$currentPage ? 'class="active"' : '';
    $out .= "<a $cls href=\"".htmlspecialchars($link, ENT_QUOTES, 'UTF-8')."\">$p</a>";
  }
  $out .= '</div>';
  return $out;
}
?>

<!-- Reviews Tab -->
<div class="tab-content" id="reviewsTab">
  <?php if (!$reviews): ?>
    <p>No reviews yet.</p>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
      <div class="item">
        <strong><?= htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <div class="stars"><?= str_repeat('★', (int)$r['rating']); ?></div>
        <p><?= nl2br(htmlspecialchars($r['comment'], ENT_QUOTES, 'UTF-8')); ?></p>
        <small class="meta"><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
    <?php endforeach; ?>
    <?= render_reviews_pager($reviewsCount, $perPage, $pageR); ?>
  <?php endif; ?>
</div>