document.addEventListener("DOMContentLoaded", function() {
    var tocContent = document.getElementById('toc-content');
    var tocLoading = document.getElementById('toc-loading');

    if (tocContent && tocLoading) {
        tocContent.style.display = 'block';
        tocLoading.style.display = 'none';
    }
});

