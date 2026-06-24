@if ($paginator->hasPages())
    <nav class="sedes-pagination" role="navigation" aria-label="Paginación">
        <div class="sedes-pagination-info">
            Mostrando {{ $paginator->firstItem() }} - {{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </div>

        <div class="sedes-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="sedes-page-link disabled">Anterior</span>
            @else
                <a class="sedes-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="sedes-page-link disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="sedes-page-link active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="sedes-page-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="sedes-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
                <span class="sedes-page-link disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
