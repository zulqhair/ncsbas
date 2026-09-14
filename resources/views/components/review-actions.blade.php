@props(['review'])
<div class="review-actions">
    @if($review->status === 'pending')
        <form method="POST" action="{{ route('reviews.update', $review) }}" data-loading-form>
            @csrf @method('PATCH')<input type="hidden" name="action" value="accept">
            <button class="btn btn-primary" type="submit" data-loading-label="Accepting…"><x-icon name="check" /> Accept review</button>
        </form>
    @endif
    @if($review->status === 'accepted')
        <form method="POST" action="{{ route('reviews.update', $review) }}" data-loading-form>
            @csrf @method('PATCH')<input type="hidden" name="action" value="complete">
            <button class="btn btn-success" type="submit" data-loading-label="Completing…"><x-icon name="check" /> Mark completed</button>
        </form>
    @endif
    @if(in_array($review->status, ['pending', 'accepted'], true))
        <details><summary>Decline this review</summary>
            <form class="decline-form" method="POST" action="{{ route('reviews.update', $review) }}" data-loading-form>
                @csrf @method('PATCH')<input type="hidden" name="action" value="decline">
                <div><label class="form-label" for="decline-reason-{{ $review->id }}">Reason <span class="text-secondary">(optional)</span></label><input class="form-control" id="decline-reason-{{ $review->id }}" name="decline_reason" maxlength="1000"></div>
                <button class="btn btn-outline-danger" type="submit" data-loading-label="Declining…">Decline review</button>
            </form>
        </details>
    @else
        <p class="text-secondary mb-0">{{ $review->status === 'completed' ? 'This review is complete. The assessment remains available for reference.' : 'This assignment was declined. An administrator can assign another reviewer.' }}</p>
    @endif
</div>
@if($review->decline_reason)<div class="alert alert-info mt-3 mb-0"><strong>Decline reason:</strong> {{ $review->decline_reason }}</div>@endif
