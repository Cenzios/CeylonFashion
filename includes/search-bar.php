<div class="container-fluid pt-3">
  <div class="row">
    <div class="col-12 col-md-6 col-lg-4">
      <form id="mainSearchForm" class="d-flex" role="search" method="GET" action="search-results.php">
        <input 
          class="form-control me-2 search-input" 
          type="search" 
          name="search"
          id="searchInput"
          placeholder="Search for products" 
          aria-label="Search" 
          style="height: 38px;" 
          required
        />
        <input type="hidden" name="category" id="searchCategoryInput" value="">
        <button class="btn btn-search" type="submit" style="height: 38px;">
          Search
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Search Filter Modal -->
<div class="modal fade" id="searchFilterModal" tabindex="-1" aria-labelledby="searchFilterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="searchFilterModalLabel">Filter by Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Select a category to refine your search (optional):</p>
        <select class="form-select" id="modalCategorySelect">
          <option value="">All Categories</option>
          <option value="bridalAttire">Bridal Attire</option>
          <option value="bridemaidAttire">Bridemaids Attire</option>
          <option value="partyWear">Party Wear</option>
          <option value="used">Used Collection</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnContinueSearch">Continue</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchForm = document.getElementById('mainSearchForm');
  const searchInput = document.getElementById('searchInput');
  const categoryInput = document.getElementById('searchCategoryInput');
  const filterModalEl = document.getElementById('searchFilterModal');
  const continueBtn = document.getElementById('btnContinueSearch');
  const categorySelect = document.getElementById('modalCategorySelect');
  
  let filterModal = null;
  if (filterModalEl) {
    // Check if bootstrap is available
    if (typeof bootstrap !== 'undefined') {
      filterModal = new bootstrap.Modal(filterModalEl);
    }
  }

  if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
      // If category is already set (via Continue button flow), let it submit
      // But we need a way to distinguish. 
      // Actually, we can just check if the modal was shown.
      // Simpler: Prevent default, show modal. The "Continue" button will submit.
      
      // However, if we just want to show modal every time "Search" is clicked:
      if (filterModal) {
        e.preventDefault();
        filterModal.show();
      }
      // If no bootstrap (fallback), just submit normally (category will be empty)
    });
  }

  if (continueBtn) {
    continueBtn.addEventListener('click', function() {
      // Set category value
      categoryInput.value = categorySelect.value;
      
      // Submit form programmatically
      // We need to bypass the submit event listener we added
      // HTMLFormElement.prototype.submit.call(searchForm) works, 
      // but standard submit() usually doesn't trigger 'submit' event handler in JS.
      searchForm.submit();
      
      if (filterModal) {
        filterModal.hide();
      }
    });
  }
});
</script>