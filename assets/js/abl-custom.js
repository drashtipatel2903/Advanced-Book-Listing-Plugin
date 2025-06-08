jQuery(document).ready(function($) {
    // Handle AJAX load more
    $('#load-more-books').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var page = parseInt($button.data('page')) + 1;
        var maxPages = parseInt($button.data('max-pages'));
        $button.prop('disabled', true).text(advanced_book_listing_ajax.loading_text || 'Loading...');
        
        // Get current filter values
        var authorLetter = $('#author_letter').val() || '';
        var priceRange = $('#price_range').val() || '';
        var sortBy = $('#sort_by').val() || 'newest';
        
        // AJAX request
        $.ajax({
            url: advanced_book_listing_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_books',
                nonce: advanced_book_listing_ajax.nonce,
                page: page,
                author_letter: authorLetter,
                price_range: priceRange,
                sort_by: sortBy,
                posts_per_page: $button.data('posts-per-page') || 3
            },
            success: function(response) {
                if (response.success) {
                    // Append new books
                    $('#book-list').append(response.data.html);
                    
                    // Update button data
                    $button.data('page', page);
                    $button.data('max-pages', response.data.max_pages);
                    
                    // Hide button if no more pages
                    if (page >= response.data.max_pages) {
                        $button.hide();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
            },
            complete: function() {
                $button.prop('disabled', false).text(advanced_book_listing_ajax.load_more_text || 'Load More');
            }
        });
    });
    
    // Handle filter form submission (AJAX)
    $('#book-filters-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var url = $form.attr('action') || window.location.href;
        var params = $form.serialize();
        
        window.location.href = url.split('?')[0] + '?' + params;
    });
});