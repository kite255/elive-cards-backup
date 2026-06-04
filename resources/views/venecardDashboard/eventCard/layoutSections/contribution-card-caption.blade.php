<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
        <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 text-primary"><i class="fas fa-heart me-2"></i>contribution card caption</h5>
                </div>
                @if($event->event_type == 'invitation')
                <div class="card-body">
                    <h4>this part is for contribution events only</h4>
                </div>
                @else
                <div class="card-body">
                    @if (optional($event->contributioncardcaption)->caption)
                        <form action="{{ route('contribution-caption.update', encrypt($event->id)) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <textarea name="contribution_card_caption" class="form-control" rows="6"
                                    placeholder="Familia ya Mzee Malingumu inapenda kuchukua nafasi hii kukuomba mchango wa Harusi ya kijana wao John Malingumu...">{{ $event->contributioncardcaption->caption }}</textarea>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-info"><i class="fas fa-edit me-2"></i>update caption</button>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('contribution-caption.store') }}" method="POST">
                            @csrf
                            <input type="text" value="{{ encrypt($event->id) }}" name="event_id" hidden readonly>
                            <div class="form-group">
                                <textarea name="contribution_card_caption" class="form-control" rows="6"
                                    placeholder="Familia ya Mzee Malingumu inapenda kuchukua nafasi hii kukuomba mchango wa Harusi ya kijana wao John Malingumu..."></textarea>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-success"><i class="fas fa-plus me-2"></i>save caption</button>
                            </div>
                        </form>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>