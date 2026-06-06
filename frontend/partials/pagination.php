<?php
/**
 * Static Pagination Partial
 * partials/pagination.php
 *
 * Visual-only component — no JavaScript needed.
 * Drop this inside any .card-body-custom, right after the closing
 * </div> of .table-responsive.
 *
 * Usage:
 *   <?php include '../partials/pagination.php'; ?>
 */
?>

<div class="qa-pagination">

    <!-- Left: entry count info -->
    <div class="qa-pagination__info">
        Showing <strong>1&ndash;10</strong> of <strong>47</strong> entries
    </div>

    <div class="qa-pagination__right">

        <!-- Rows per page selector -->
        <div class="qa-pagination__per-page">
            <span>Rows per page:</span>
            <select class="form-control-qa qa-pagination__select" disabled>
                <option selected>10</option>
                <option>25</option>
                <option>50</option>
            </select>
        </div>

        <!-- Page buttons -->
        <nav aria-label="Table pagination">
            <ul class="qa-page-list">

                <!-- Previous (disabled on page 1) -->
                <li>
                    <button class="qa-page-btn" disabled title="Previous page">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </li>

                <!-- Page 1 – active -->
                <li>
                    <button class="qa-page-btn qa-page-btn--active" aria-current="page">1</button>
                </li>

                <!-- Pages 2–3 -->
                <li><button class="qa-page-btn">2</button></li>
                <li><button class="qa-page-btn">3</button></li>

                <!-- Ellipsis -->
                <li><span class="qa-page-ellipsis">&hellip;</span></li>

                <!-- Last page -->
                <li><button class="qa-page-btn">5</button></li>

                <!-- Next -->
                <li>
                    <button class="qa-page-btn" title="Next page">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </li>

            </ul>
        </nav>

    </div><!-- /qa-pagination__right -->

</div><!-- /qa-pagination -->


<style>
/* ── QA Pagination ───────────────────────────────────────────────────────────
   Scoped to .qa-pagination so it won't affect anything else on the page.
   All colours reference the same CSS variables used in styles.css so the
   component automatically adapts to light/dark themes without extra work.
   ────────────────────────────────────────────────────────────────────────── */

.qa-pagination {
    display        : flex;
    align-items    : center;
    justify-content: space-between;
    flex-wrap      : wrap;
    gap            : 12px;
    padding        : 16px 0 4px;
    margin-top     : 12px;
    border-top     : 1px solid var(--border, #e5e7eb);
}

/* ── Left label ── */
.qa-pagination__info {
    font-size : .83rem;
    color     : var(--text-secondary, #6b7280);
    white-space: nowrap;
}

.qa-pagination__info strong {
    color      : var(--text-primary, #111827);
    font-weight: 700;
}

/* ── Right cluster (rows-per-page + nav) ── */
.qa-pagination__right {
    display    : flex;
    align-items: center;
    gap        : 20px;
    flex-wrap  : wrap;
}

/* ── Rows-per-page ── */
.qa-pagination__per-page {
    display    : flex;
    align-items: center;
    gap        : 8px;
    font-size  : .83rem;
    color      : var(--text-secondary, #6b7280);
    white-space: nowrap;
}

.qa-pagination__select {
    width  : 70px !important;
    padding: 5px 8px !important;
    cursor : not-allowed;    /* static — no interaction */
    opacity: 1;
}

/* ── Page button list ── */
.qa-page-list {
    list-style: none;
    margin    : 0;
    padding   : 0;
    display   : flex;
    align-items: center;
    gap       : 4px;
}

/* ── Individual page button ── */
.qa-page-btn {
    display        : inline-flex;
    align-items    : center;
    justify-content: center;
    min-width      : 34px;
    height         : 34px;
    padding        : 0 10px;
    border         : 1px solid var(--border, #e5e7eb);
    border-radius  : var(--radius, 8px);
    background     : var(--bg-card, #ffffff);
    color          : var(--text-primary, #111827);
    font-size      : .83rem;
    font-weight    : 600;
    cursor         : pointer;
    transition     : background .15s, border-color .15s, color .15s;
    line-height    : 1;
}

.qa-page-btn:hover:not(:disabled):not(.qa-page-btn--active) {
    background  : var(--bg-main, #f9fafb);
    border-color: var(--primary, #4f46e5);
    color       : var(--primary, #4f46e5);
}

/* Active / current page */
.qa-page-btn--active {
    background  : var(--primary, #4f46e5);
    border-color: var(--primary, #4f46e5);
    color       : #ffffff;
    cursor      : default;
    pointer-events: none;
}

/* Disabled (e.g. prev on page 1) */
.qa-page-btn:disabled {
    opacity       : .38;
    cursor        : not-allowed;
    pointer-events: none;
}

/* ── Ellipsis ── */
.qa-page-ellipsis {
    display   : inline-flex;
    align-items: center;
    padding   : 0 4px;
    color     : var(--text-secondary, #6b7280);
    font-weight: 700;
    user-select: none;
    font-size : .9rem;
}

/* ── Responsive: stack on narrow screens ── */
@media (max-width: 576px) {
    .qa-pagination {
        flex-direction: column;
        align-items   : flex-start;
    }

    .qa-pagination__right {
        width: 100%;
        justify-content: space-between;
    }
}
</style>