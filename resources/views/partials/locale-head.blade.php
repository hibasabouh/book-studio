{{-- Apply the stored locale's direction before paint to avoid an LTR/RTL flash. --}}
<script>
    (function () {
        try {
            var l = localStorage.getItem('locale');
            if (['en', 'ar', 'fr'].indexOf(l) < 0) l = 'en';
            document.documentElement.lang = l;
            document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
        } catch (e) { console.error(e); }
    })();
</script>
