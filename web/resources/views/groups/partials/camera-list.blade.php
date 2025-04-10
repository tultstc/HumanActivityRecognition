@foreach ($cameras as $camera)
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">{{ $camera->name }}</h5>
                    <a href="{{ route('cameras.edit', $camera->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>

                @if ($camera->stream_url)
                    <div class="flex">
                        <strong>URL:</strong>
                        <p class="ml-2 line-clamp-1 italic">
                            {{ $camera->stream_url }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
