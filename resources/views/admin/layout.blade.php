<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Admin') · Back Then Stories</title><style>
:root{--bg:#f5f6f8;--card:#fff;--ink:#17202a;--muted:#6b7280;--line:#e5e7eb;--brand:#7a2f22;--nav:#15171a}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,Arial}.layout{display:grid;grid-template-columns:240px 1fr;min-height:100vh}.side{background:var(--nav);color:#fff;padding:22px 14px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column}.logo{font-family:Georgia,serif;font-size:20px;font-weight:800;padding:0 10px 20px}.nav a{display:block;color:#d1d5db;text-decoration:none;padding:10px 12px;border-radius:8px;margin:2px 0}.nav a:hover,.nav a.active{background:#292d32;color:#fff}.main{padding:28px}.topbar{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:24px}.topbar h1{margin:0;font-size:26px}.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;box-shadow:0 1px 2px #00000008}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.stat b{font-size:28px;display:block}.muted{color:var(--muted)}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:12px;text-transform:uppercase;color:var(--muted)}.btn{display:inline-flex;align-items:center;justify-content:center;background:var(--brand);color:#fff;border:0;border-radius:8px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer}.btn.secondary{background:#fff;color:var(--ink);border:1px solid var(--line)}.btn.danger{background:#a51d2d}.grid2{display:grid;grid-template-columns:2fr 1fr;gap:18px}.field{margin-bottom:16px}.field label{font-weight:700;font-size:13px;display:block;margin-bottom:6px}.field input,.field select,.field textarea{width:100%;padding:10px 12px;border:1px solid #cfd4dc;border-radius:8px;background:#fff;font:inherit}.field textarea{min-height:120px}.editorbar{display:flex;gap:6px;flex-wrap:wrap;padding:8px;border:1px solid #cfd4dc;border-bottom:0;border-radius:8px 8px 0 0;background:#fafafa}.editorbar button{border:1px solid #ddd;background:#fff;border-radius:6px;padding:6px 9px}.editor{min-height:420px;padding:16px;border:1px solid #cfd4dc;border-radius:0 0 8px 8px;background:white;overflow:auto}.editor:focus{outline:2px solid #7a2f2230}.flash{background:#eaf8ef;border:1px solid #b8e5c6;color:#22633a;border-radius:8px;padding:10px 12px;margin-bottom:16px}.errors{background:#fff0f1;border:1px solid #f0b6bc;color:#8d1d2b;border-radius:8px;padding:10px 14px;margin-bottom:16px}.badge{display:inline-block;border-radius:999px;padding:4px 8px;background:#eee;font-size:12px}.imgthumb{width:90px;height:60px;object-fit:cover;border-radius:6px}.media-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.media-item img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px}.small{font-size:12px}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.pagination{margin-top:18px}.pagination svg{width:18px}
.admin-lang-switcher{
    margin-top:auto;
    padding:18px 10px 4px;
    border-top:1px solid rgba(255,255,255,.12);
}
.admin-lang-title{
    color:#9ca3af;
    font-size:10px;
    font-weight:800;
    letter-spacing:.08em;
    line-height:1.35;
    text-transform:uppercase;
    margin-bottom:9px;
}
.admin-lang-buttons{
    display:flex;
    align-items:center;
    gap:6px;
}
.admin-lang-btn{
    border:1px solid rgba(255,255,255,.18);
    background:transparent;
    color:#d1d5db;
    border-radius:999px;
    min-width:37px;
    height:34px;
    padding:0 10px;
    font-weight:800;
    font-size:11px;
    cursor:pointer;
}
.admin-lang-btn:hover{
    background:#292d32;
    color:#fff;
}
.admin-lang-btn.active{
    background:#fff;
    color:#15171a;
    border-color:#fff;
}
@media(max-width:900px){.layout{grid-template-columns:1fr}.side{position:relative;height:auto}.main{padding:16px}.stats{grid-template-columns:1fr 1fr}.grid2{grid-template-columns:1fr}.media-grid{grid-template-columns:repeat(2,1fr)}}
</style>@stack('head')</head><body><div class="layout"><aside class="side"><div class="logo">BACK THEN STORIES</div><nav class="nav"><a href="{{ route('admin.dashboard') }}">Dashboard</a><a href="{{ route('admin.articles.index') }}">Articles</a><a href="{{ route('admin.artists.index') }}">Artists</a><a href="{{ route('admin.categories.index') }}">Categories</a><a href="{{ route('admin.media.index') }}">Media</a>@if(auth()->user()->role==='admin')<a href="{{ route('admin.sites.index') }}">Sites</a><a href="{{ route('admin.ads.index') }}">Ad Manager</a>@endif<a href="{{ route('home') }}" target="_blank">View website ↗</a></nav>
<div class="admin-lang-switcher" aria-label="Admin UI language">
    <div class="admin-lang-title" id="adminLangTitle">
        Admin UI language
    </div>
    <div class="admin-lang-buttons">
        <button
            type="button"
            class="admin-lang-btn"
            data-admin-lang="en"
        >
            EN
        </button>
        <button
            type="button"
            class="admin-lang-btn"
            data-admin-lang="vi"
        >
            VI
        </button>
    </div>
</div>
</aside><main class="main"><div class="topbar"><h1>@yield('heading','Dashboard')</h1><div class="row"><span class="muted">{{ auth()->user()->name }}</span><form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn secondary" type="submit">Logout</button></form></div></div>@if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif @if($errors->any())<div class="errors"><b>Please fix:</b><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif @yield('content')</main></div>@stack('scripts')
<script>
(function () {
    const STORAGE_KEY =
        'back_then_admin_ui_language';

    const VI = {
        /* Sidebar / account */
        'Dashboard': 'Bảng điều khiển',
        'Articles': 'Bài viết',
        'Artists': 'Nghệ sĩ',
        'Categories': 'Danh mục',
        'Media': 'Thư viện phương tiện',
        'Sites': 'Trang web',
        'Ad Manager': 'Quản lý quảng cáo',
        'View website ↗': 'Xem website ↗',
        'Logout': 'Đăng xuất',
        'Administrator': 'Quản trị viên',
        'Admin UI language': 'Ngôn ngữ giao diện quản trị',

        /* Common pages */
        'New Article': 'Bài viết mới',
        'Edit Article': 'Chỉnh sửa bài viết',
        'Article': 'Bài viết',
        'Article title': 'Tiêu đề bài viết',
        'Title': 'Tiêu đề',
        'Slug': 'Slug',
        'Slug (leave blank to auto-generate)': 'Slug (để trống để tự tạo)',
        'Status': 'Trạng thái',
        'Site': 'Trang web',
        'Artist': 'Nghệ sĩ',
        'Category': 'Danh mục',
        'Featured image': 'Ảnh đại diện',
        'Featured story': 'Bài nổi bật',
        'Publish at': 'Thời gian đăng',
        'Created': 'Đã tạo',
        'Updated': 'Đã cập nhật',
        'Actions': 'Thao tác',
        'Action': 'Thao tác',
        'Views': 'Lượt xem',
        'Published': 'Đã xuất bản',
        'Draft': 'Bản nháp',
        'Review': 'Chờ duyệt',
        'Scheduled': 'Đã lên lịch',
        'Trashed': 'Thùng rác',
        'Active': 'Đang hoạt động',
        'All': 'Tất cả',
        'Apply': 'Áp dụng',
        'Clear': 'Xóa lọc',
        'Search': 'Tìm kiếm',
        'Filter': 'Bộ lọc',
        'Rows per page': 'Số dòng mỗi trang',
        'All articles': 'Tất cả bài viết',
        'All sites': 'Tất cả trang web',
        'All categories': 'Tất cả danh mục',
        'All statuses': 'Tất cả trạng thái',
        'No articles found.': 'Không tìm thấy bài viết.',
        '+ New article': '+ Bài viết mới',
        'Preview ↗': 'Xem trước ↗',
        'Open ↗': 'Mở ↗',
        'Copy': 'Sao chép',
        'Delete': 'Xóa',
        'Delete article': 'Xóa bài viết',
        'Save article': 'Lưu bài viết',
        'Restore': 'Khôi phục',
        'Permanent delete': 'Xóa vĩnh viễn',
        'Link copied': 'Đã sao chép liên kết',

        /* Import */
        'Import from URL': 'Nhập từ URL',
        'Import from URL (public article pages only)': 'Nhập từ URL (chỉ trang bài viết công khai)',
        'Featured image': 'Ảnh đại diện',
        'Rewrite body with AI': 'Viết lại nội dung bằng AI',
        'Generate Opening + SEO': 'Tạo mở bài + SEO',
        '↓ Import article': '↓ Nhập bài viết',
        'Imports title and clean article text. Use only content you are allowed to reuse.':
            'Nhập tiêu đề và nội dung bài viết đã làm sạch. Chỉ sử dụng nội dung bạn được phép dùng lại.',

        /* Article editor */
        'Content mode': 'Chế độ nội dung',
        'Normal': 'Bình thường',
        'Chapter': 'Chương',
        'Skip intro — go directly to Chapter 1': 'Bỏ qua phần mở đầu — đi thẳng tới Chương 1',
        'When enabled, visitors opening the main story URL are redirected to Chapter 1.':
            'Khi bật, người đọc mở URL bài chính sẽ được chuyển thẳng tới Chương 1.',
        'Chapter mode creates separate public pages with Previous / Next navigation.':
            'Chế độ Chương tạo các trang riêng với điều hướng Trước / Tiếp theo.',
        'Alternate slugs — extra URLs for the same article':
            'Slug thay thế — các URL bổ sung cho cùng một bài viết',
        'Alternate slugs': 'Slug thay thế',
        'QTY': 'Số lượng',
        '✨ Generate random': '✨ Tạo ngẫu nhiên',
        '+ Add alias': '+ Thêm slug',
        'Opening excerpt / deck': 'Mở bài / mô tả ngắn',
        'Article body': 'Nội dung bài viết',
        'INTRO / DESCRIPTION': 'MỞ BÀI / MÔ TẢ',
        'Chapters': 'Chương',
        '+ Add Chapter': '+ Thêm chương',
        'Chapter title': 'Tiêu đề chương',
        'No chapters yet. Click “Add Chapter” or use “Analyze Chapters”.':
            'Chưa có chương nào. Nhấn “Thêm chương” hoặc dùng “Phân tích chương”.',
        '✨ Analyze Chapters': '✨ Phân tích chương',
        '✨ Generate Opening + SEO with AI': '✨ Tạo mở bài + SEO bằng AI',
        '✨ Rewrite Article Uniquely with AI': '✨ Viết lại bài độc đáo bằng AI',
        '↶ Restore original': '↶ Khôi phục bản gốc',
        'YouTube': 'YouTube',
        'Choose Image': 'Chọn ảnh',
        'Image URL': 'URL ảnh',
        'Quote': 'Trích dẫn',
        'Link': 'Liên kết',
        'Unlink': 'Bỏ liên kết',
        'lower': 'chữ thường',
        'Media Library': 'Thư viện phương tiện',
        'Upload new image': 'Tải ảnh mới',
        'Search filename or alt text...': 'Tìm tên tệp hoặc văn bản thay thế...',
        'Load more': 'Tải thêm',
        'No images found.': 'Không tìm thấy ảnh.',
        'Upload images': 'Tải ảnh lên',
        'Images': 'Ảnh',
        'Alt text (optional)': 'Văn bản thay thế (không bắt buộc)',
        'Describe the image': 'Mô tả hình ảnh',
        'Upload': 'Tải lên',
        'Search library': 'Tìm trong thư viện',
        'Filename or alt text...': 'Tên tệp hoặc văn bản thay thế...',
        'Copy URL': 'Sao chép URL',
        'Save': 'Lưu',
        'Alt text': 'Văn bản thay thế',
        'Copied': 'Đã sao chép',
        'Close': 'Đóng',

        /* SEO */
        'SEO': 'SEO',
        'SEO title': 'Tiêu đề SEO',
        'Meta description': 'Mô tả meta',
        'Meta description (AI target: ~140–160 characters)':
            'Mô tả meta (mục tiêu AI: khoảng 140–160 ký tự)',
        'Search preview': 'Xem trước tìm kiếm',
        'Add a meta description to preview how this article may appear in search results.':
            'Thêm mô tả meta để xem trước cách bài viết có thể xuất hiện trong kết quả tìm kiếm.',
        'Preview only — Google may rewrite the title or description shown in search results.':
            'Chỉ là bản xem trước — Google có thể viết lại tiêu đề hoặc mô tả trong kết quả tìm kiếm.',

        /* Dashboard */
        'Performance dashboard': 'Bảng điều khiển hiệu suất',
        'Total ads': 'Tổng quảng cáo',
        'Active ads': 'Quảng cáo đang hoạt động',
        'Changes · 7 days': 'Thay đổi · 7 ngày',
        'Ads changed · 7 days': 'Quảng cáo đã sửa · 7 ngày',
        'Post Views Overview': 'Tổng quan lượt xem bài viết',
        'Published posts': 'Bài đã xuất bản',
        'Total post views': 'Tổng lượt xem bài viết',
        'Average views / post': 'Lượt xem trung bình / bài',
        'Article views by day': 'Lượt xem bài theo ngày',
        'Top Viewed Posts': 'Bài được xem nhiều nhất',
        'Popular articles': 'Bài viết phổ biến',
        'Export URLs (.txt)': 'Xuất URL (.txt)',
        'Ad change speed · 14 days': 'Tốc độ thay đổi quảng cáo · 14 ngày',
        'Most edited ads · 30 days': 'Quảng cáo được sửa nhiều nhất · 30 ngày',
        'My site access': 'Quyền truy cập trang của tôi',
        'Article activity · 30 days': 'Hoạt động bài viết · 30 ngày',
        'Recent ad updates': 'Cập nhật quảng cáo gần đây',
        'From': 'Từ',
        'To': 'Đến',
        'Time': 'Thời gian',
        'User': 'Người dùng',
        'Fields changed': 'Trường đã thay đổi',
        'Current role': 'Vai trò hiện tại',
        'Sites available': 'Trang có thể truy cập',
        'Open Articles': 'Mở Bài viết',
        'No published articles yet.': 'Chưa có bài viết nào được xuất bản.',
        'No ad changes tracked yet. Tracking begins after this upgrade.':
            'Chưa ghi nhận thay đổi quảng cáo. Theo dõi bắt đầu từ bản nâng cấp này.',
        'No ad edits tracked yet.': 'Chưa ghi nhận chỉnh sửa quảng cáo.',
        'Daily view history begins after this analytics upgrade.':
            'Lịch sử lượt xem theo ngày bắt đầu từ bản nâng cấp phân tích này.',

        /* Misc admin */
        'Please fix:': 'Vui lòng sửa:',
        'Back Then Stories': 'Back Then Stories',
        '— none —': '— không có —',
        'NO IMG': 'KHÔNG ẢNH',
        'Thumb': 'Ảnh',
        'Site / Author': 'Trang / Tác giả',
        'Manage, filter, sort, restore and track article performance.':
            'Quản lý, lọc, sắp xếp, khôi phục và theo dõi hiệu suất bài viết.',
        'Title or slug...': 'Tiêu đề hoặc slug...',
        'Save this article once, then Edit it to create alternate URLs.':
            'Lưu bài viết một lần, sau đó Chỉnh sửa để tạo URL thay thế.'
    };

    const PLACEHOLDERS_VI = {
        'Title or slug...': 'Tiêu đề hoặc slug...',
        'Chapter title': 'Tiêu đề chương',
        'https://example.com/article': 'https://example.com/article'
    };

    function currentLanguage() {
        return localStorage.getItem(
            STORAGE_KEY
        ) || 'en';
    }

    function shouldSkip(node) {
        const parent =
            node.nodeType === Node.ELEMENT_NODE
                ? node
                : node.parentElement;

        if (!parent) {
            return true;
        }

        return !!parent.closest(
            '.editor,'
            + '.chapter-rich-editor,'
            + '[contenteditable="true"],'
            + 'textarea,'
            + 'input,'
            + 'pre,'
            + 'code,'
            + 'script,'
            + 'style'
        );
    }

    function preserveWhitespace(original, translated) {
        const leading =
            original.match(/^\s*/)?.[0]
            || '';

        const trailing =
            original.match(/\s*$/)?.[0]
            || '';

        return leading
            + translated
            + trailing;
    }

    function translateDynamicText(text) {
        if (VI[text]) {
            return VI[text];
        }

        let match =
            text.match(
                /^Chapters\s*\((\d+)\)$/i
            );

        if (match) {
            return 'Chương ('
                + match[1]
                + ')';
        }

        match =
            text.match(
                /^Chapter\s+(\d+)$/i
            );

        if (match) {
            return 'Chương '
                + match[1];
        }

        match =
            text.match(
                /^Chapter\s+(\d+)\s+·\s+([\d,.]+)\s+views$/i
            );

        if (match) {
            return 'Chương '
                + match[1]
                + ' · '
                + match[2]
                + ' lượt xem';
        }

        match =
            text.match(
                /^(\d+)\s+alternate URL\(s\)$/i
            );

        if (match) {
            return match[1]
                + ' URL thay thế';
        }

        match =
            text.match(
                /^(\d+)\s+chapter\(s\)\s+added\.\s+Save the article when ready\.$/i
            );

        if (match) {
            return 'Đã thêm '
                + match[1]
                + ' chương. Lưu bài viết khi hoàn tất.';
        }

        match =
            text.match(
                /^(\d+)\s+AI chapter\(s\)\s+created\.\s+Review them before saving\.$/i
            );

        if (match) {
            return 'AI đã tạo '
                + match[1]
                + ' chương. Hãy kiểm tra trước khi lưu.';
        }

        return text;
    }

    function translateTextNode(node) {
        if (
            currentLanguage() !== 'vi'
            || shouldSkip(node)
        ) {
            return;
        }

        const original =
            node.nodeValue;

        const trimmed =
            original.trim();

        if (!trimmed) {
            return;
        }

        const translated =
            translateDynamicText(
                trimmed
            );

        if (translated !== trimmed) {
            node.nodeValue =
                preserveWhitespace(
                    original,
                    translated
                );
        }
    }

    function translateAttributes(root) {
        if (
            currentLanguage() !== 'vi'
        ) {
            return;
        }

        const elements = [];

        if (
            root.nodeType
            === Node.ELEMENT_NODE
        ) {
            elements.push(root);
        }

        root.querySelectorAll?.(
            '[placeholder],[title],[aria-label]'
        ).forEach(
            function (el) {
                elements.push(el);
            }
        );

        elements.forEach(
            function (el) {
                if (
                    el.closest(
                        '.editor,'
                        + '.chapter-rich-editor,'
                        + '[contenteditable="true"]'
                    )
                ) {
                    return;
                }

                ['placeholder', 'title', 'aria-label']
                    .forEach(
                        function (attr) {
                            const value =
                                el.getAttribute(
                                    attr
                                );

                            if (!value) {
                                return;
                            }

                            const translated =
                                VI[value]
                                || PLACEHOLDERS_VI[value]
                                || translateDynamicText(
                                    value
                                );

                            if (
                                translated
                                !== value
                            ) {
                                el.setAttribute(
                                    attr,
                                    translated
                                );
                            }
                        }
                    );
            }
        );
    }

    function translateRoot(root) {
        if (
            currentLanguage() !== 'vi'
        ) {
            return;
        }

        if (
            root.nodeType === Node.TEXT_NODE
        ) {
            translateTextNode(root);
            return;
        }

        const walker =
            document.createTreeWalker(
                root,
                NodeFilter.SHOW_TEXT
            );

        let node;

        while (
            (node = walker.nextNode())
        ) {
            translateTextNode(node);
        }

        translateAttributes(root);
    }

    function setActiveLanguageButton() {
        const lang =
            currentLanguage();

        document
            .querySelectorAll(
                '[data-admin-lang]'
            )
            .forEach(
                function (button) {
                    button.classList.toggle(
                        'active',
                        button.dataset
                            .adminLang
                        === lang
                    );
                }
            );

        document.documentElement.lang =
            lang === 'vi'
                ? 'vi'
                : 'en';

        const title =
            document.getElementById(
                'adminLangTitle'
            );

        if (title) {
            title.textContent =
                lang === 'vi'
                    ? 'Ngôn ngữ giao diện quản trị'
                    : 'Admin UI language';
        }
    }

    document
        .querySelectorAll(
            '[data-admin-lang]'
        )
        .forEach(
            function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        const next =
                            this.dataset
                                .adminLang;

                        if (
                            next !== 'en'
                            && next !== 'vi'
                        ) {
                            return;
                        }

                        localStorage.setItem(
                            STORAGE_KEY,
                            next
                        );

                        window.location.reload();
                    }
                );
            }
        );

    setActiveLanguageButton();

    if (
        currentLanguage() === 'vi'
    ) {
        translateRoot(
            document.body
        );

        const observer =
            new MutationObserver(
                function (mutations) {
                    mutations.forEach(
                        function (mutation) {
                            mutation.addedNodes
                                .forEach(
                                    function (node) {
                                        translateRoot(
                                            node
                                        );
                                    }
                                );
                        }
                    );
                }
            );

        observer.observe(
            document.body,
            {
                childList: true,
                subtree: true
            }
        );

        const nativeAlert =
            window.alert.bind(
                window
            );

        const nativeConfirm =
            window.confirm.bind(
                window
            );

        const nativePrompt =
            window.prompt.bind(
                window
            );

        window.alert =
            function (message) {
                return nativeAlert(
                    translateDynamicText(
                        String(message)
                    )
                );
            };

        window.confirm =
            function (message) {
                const map = {
                    'Delete this alternate URL?':
                        'Xóa URL thay thế này?',
                    'Remove this chapter? It will be deleted when you save the article.':
                        'Xóa chương này? Chương sẽ bị xóa khi bạn lưu bài viết.',
                    'AI analysis will replace the current chapter list. Continue?':
                        'Phân tích AI sẽ thay thế danh sách chương hiện tại. Tiếp tục?'
                };

                return nativeConfirm(
                    map[message]
                    || translateDynamicText(
                        String(message)
                    )
                );
            };

        window.prompt =
            function (
                message,
                defaultValue
            ) {
                const map = {
                    'Enter an alternate slug.':
                        'Nhập slug thay thế.',
                    'Paste link URL':
                        'Dán URL liên kết',
                    'Paste YouTube URL':
                        'Dán URL YouTube',
                    'Paste image URL':
                        'Dán URL ảnh'
                };

                return nativePrompt(
                    map[message]
                    || translateDynamicText(
                        String(message)
                    ),
                    defaultValue
                );
            };
    }
})();
</script>

</body></html>
