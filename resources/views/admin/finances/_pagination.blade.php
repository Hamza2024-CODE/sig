<div class="fin-pages">
    @if($data['pages'] > 1)
        @php
            $currentPage = $data['page'];
            $totalPages  = $data['pages'];
            $baseUrl     = url()->current();
            $params      = array_merge(request()->all(), ['tab' => $tab]);
        @endphp
        <button class="fin-page-btn" onclick="goPage({{ max(1,$currentPage-1) }},'{{ $tab }}')" {{ $currentPage<=1?'disabled':'' }}>
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        @for($i = max(1,$currentPage-2); $i <= min($totalPages,$currentPage+2); $i++)
            <button class="fin-page-btn {{ $i===$currentPage?'active':'' }}" onclick="goPage({{ $i }},'{{ $tab }}')">{{ $i }}</button>
        @endfor
        <button class="fin-page-btn" onclick="goPage({{ min($totalPages,$currentPage+1) }},'{{ $tab }}')" {{ $currentPage>=$totalPages?'disabled':'' }}>
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    @endif
</div>
<script>
function goPage(p, tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', p);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}
</script>
