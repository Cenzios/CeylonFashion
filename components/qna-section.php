<?php
// ------------------------------
// components/qna-section.php
// Q&A display component
// ------------------------------

function render_qna_pager($total, $perPage, $currentPage) {
  $pages = (int)ceil(max(1, $total)/$perPage);
  if ($pages <= 1) return '';
  $out = '<div class="pager">';
  for ($p = 1; $p <= $pages; $p++) {
    $params = $_GET;
    $params['pageQ'] = $p;
    $link = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    $cls = $p === (int)$currentPage ? 'class="active"' : '';
    $out .= "<a $cls href=\"".htmlspecialchars($link, ENT_QUOTES, 'UTF-8')."\">$p</a>";
  }
  $out .= '</div>';
  return $out;
}
?>

<!-- Q&A Tab -->
<div class="tab-content" id="qnaTab" style="display:none;">
  <?php if (!$questions): ?>
    <p>No questions yet.</p>
  <?php else: ?>
    <?php foreach ($questions as $q): ?>
      <div class="item">
        <p><strong>Q:</strong> <?= nl2br(htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8')); ?></p>
        <small class="meta">By <?= htmlspecialchars($q['username'], ENT_QUOTES, 'UTF-8'); ?> • <?= htmlspecialchars($q['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
    <?php endforeach; ?>
    <?= render_qna_pager($questionsCount, $perPage, $pageQ); ?>
  <?php endif; ?>
</div>