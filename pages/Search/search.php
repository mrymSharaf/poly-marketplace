<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Search Listings';
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <h2 class="mb-1"><i class="bi bi-search me-2"></i>Search Listings</h2>
        <p class="mb-0" style="opacity:.75;">
            Find listings by keyword, creator, date, or popularity.
        </p>
    </div>
</div>

<div class="container pb-5">

    <div class="card card-pm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-4">
                    <label for="filterKeyword" class="form-label fw-semibold">
                        <i class="bi bi-type me-1 text-primary"></i>Keyword / Title
                    </label>
                    <input
                        type="text"
                        id="filterKeyword"
                        class="form-control"
                        placeholder="e.g. handmade bag, art print…"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label for="filterCreator" class="form-label fw-semibold">
                        <i class="bi bi-person me-1 text-primary"></i>Creator / Author
                    </label>
                    <input
                        type="text"
                        id="filterCreator"
                        class="form-control"
                        placeholder="e.g. Sara Ali"
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label for="filterStartDate" class="form-label fw-semibold">
                        <i class="bi bi-calendar me-1 text-primary"></i>From Date
                    </label>
                    <input type="date" id="filterStartDate" class="form-control">
                </div>

                <div class="col-6 col-md-2">
                    <label for="filterEndDate" class="form-label fw-semibold">
                        <i class="bi bi-calendar-check me-1 text-primary"></i>To Date
                    </label>
                    <input type="date" id="filterEndDate" class="form-control">
                </div>

                <div class="col-12 col-md-4">
                    <label for="filterSortBy" class="form-label fw-semibold">
                        <i class="bi bi-sort-down me-1 text-primary"></i>Sort By
                    </label>
                    <select id="filterSortBy" class="form-select">
                        <option value="newest">Newest First</option>
                        <option value="rating">Highest Average Rating</option>
                        <option value="popular">Most Rated (Popular)</option>
                    </select>
                </div>

                <div class="col-12 col-md-auto d-flex gap-2 align-items-end">
                    <button id="btnSearch" class="btn btn-accent px-4">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <button id="btnClear" class="btn btn-outline-secondary px-3" title="Clear all filters">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div id="resultsLabel" class="text-muted small mb-3" style="display:none;">
    </div>

    <div id="searchResults" class="row g-4">
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-search fs-1 d-block mb-3" style="opacity:.3;"></i>
            <p class="mb-0">Use the filters above to find listings.<br>
            <small>Searches by title, creator name, date range, or popularity.</small>
            </p>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<script>
const AJAX_URL = '<?= BASE_URL ?>/ajax/search_listings.php';

function runSearch() {
    const params = {
        keyword:   $('#filterKeyword').val().trim(),
        creator:   $('#filterCreator').val().trim(),
        startDate: $('#filterStartDate').val(),
        endDate:   $('#filterEndDate').val(),
        sortBy:    $('#filterSortBy').val()
    };

    $('#searchResults').html(
        '<div class="col-12 text-center py-5">' +
            '<div class="spinner-border text-primary" role="status" aria-label="Loading"></div>' +
            '<p class="mt-3 text-muted small">Searching…</p>' +
        '</div>'
    );
    $('#resultsLabel').hide();

    $.get(AJAX_URL, params)
        .done(function (html) {
            $('#searchResults').html(html);

            const count = $('#searchResults .listing-card').length;
            if (count > 0) {
                $('#resultsLabel')
                    .text('Showing ' + count + ' result' + (count !== 1 ? 's' : ''))
                    .show();
            }
        })
        .fail(function () {
            $('#searchResults').html(
                '<div class="col-12">' +
                    '<div class="alert alert-danger">' +
                        '<i class="bi bi-exclamation-circle me-2"></i>' +
                        'Something went wrong. Please try again.' +
                    '</div>' +
                '</div>'
            );
        });
}

$(document).ready(function () {

    const urlParams = new URLSearchParams(window.location.search);
    const navKeyword = urlParams.get('keyword');
    if (navKeyword && navKeyword.trim() !== '') {
        $('#filterKeyword').val(navKeyword.trim());
        runSearch();
    }

    $('#btnSearch').on('click', runSearch);

    $('#btnClear').on('click', function () {
        $('#filterKeyword, #filterCreator, #filterStartDate, #filterEndDate').val('');
        $('#filterSortBy').val('newest');
        $('#resultsLabel').hide();
        $('#searchResults').html(
            '<div class="col-12 text-center py-5 text-muted">' +
                '<i class="bi bi-funnel fs-1 d-block mb-3" style="opacity:.3;"></i>' +
                '<p class="mb-0">Filters cleared. Start a new search above.</p>' +
            '</div>'
        );
        history.replaceState(null, '', window.location.pathname);
    });

    $('#filterKeyword, #filterCreator').on('keypress', function (e) {
        if (e.which === 13) runSearch();
    });

});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
